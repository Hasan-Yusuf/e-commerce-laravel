<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use App\Models\Category;
use App\Models\Country;
use App\Models\Order;
use App\Models\Product;
use App\Models\Type;
use App\Models\User;
use App\Models\WishlistItem;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The storefront in one sitting, as a shopper meets it: browse the catalogue,
 * search, open a product, add one to the cart and save another, sign in, move
 * the saved one into the cart, answer the checkout, pay cash on delivery, read
 * the confirmation and find the order in the history.
 */
class StorefrontJourneyTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * A fixed, valid session id (40 alphanumeric chars) so the guest's requests
     * share one cart and one wishlist. The test client does not replay the
     * session cookie automatically, so it is sent with every request instead.
     */
    private const string SESSION_ID = 'StorefrontJourneyTest0123456789012345678';

    /**
     * The country the checkout's numbers are given for, made once per test.
     */
    private ?Country $bahrain = null;

    public function test_the_whole_journey_from_the_showcase_to_the_order_history(): void
    {
        $category = Category::factory()
            ->active()
            ->create([
                'parent_id' => null,
                'name_en' => 'Audio Gear',
            ]);
        $type = Type::factory()->create(['category_id' => $category->id]);

        $headset = $this->createProduct($type, [
            'name' => 'Gaming Headset X',
            'price_in_fils' => 12_500,
            'stock' => 5,
            'featured_at' => now()->subDay(),
        ]);

        $stand = $this->createProduct($type, [
            'name' => 'Monitor Stand',
            'price_in_fils' => 7_500,
            'stock' => 5,
        ]);

        // ── Browse: the homepage, the catalogue and a category ──
        $this
            ->asGuest()
            ->get(route('home'))
            ->assertOk()
            ->assertSee($category->name)
            ->assertSee($headset->name)
            ->assertSee(route('category.show', $category->slug), false)
            ->assertSee(route('products.show', $headset->slug), false);

        $this
            ->asGuest()
            ->get(route('category.index'))
            ->assertOk()
            ->assertSee($category->name);

        $this
            ->asGuest()
            ->get(route('category.show', $category->slug))
            ->assertOk()
            ->assertSee($headset->name)
            ->assertSee($stand->name);

        // ── Search finds the one product that matches the term ──
        $this
            ->asGuest()
            ->get(route('products.index', ['q' => 'Gaming Headset']))
            ->assertOk()
            ->assertSee($headset->name)
            ->assertDontSee($stand->name);

        // ── The product page offers both actions ──
        $this
            ->asGuest()
            ->get(route('products.show', $headset->slug))
            ->assertOk()
            ->assertSee($headset->name)
            ->assertSee('action="'.route('cart.add').'"', false)
            ->assertSee(__('homepage.add_to_cart'));

        // ── The headset goes into the cart, the stand onto the wishlist ──
        $this->addToCart($headset, 2)->assertRedirect();

        $this
            ->asGuest()
            ->post(route('wishlist.add'), ['product_id' => $stand->id])
            ->assertRedirect();

        $this
            ->asGuest()
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee($headset->name)
            ->assertSee($stand->name)
            ->assertSee(trans_choice('carts.count', 2));

        // ── Signing in takes the guest's two lists onto the account ──
        $user = $this->account();

        $this
            ->asGuest()
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'session_id' => null,
        ]);
        $this->assertDatabaseHas('wishlist_items', [
            'user_id' => $user->id,
            'product_id' => $stand->id,
        ]);

        $this->actingAs($user);

        // ── The header reports the account, and the cart badge the cart ──
        $this
            ->get(route('home'))
            ->assertOk()
            ->assertSee(__('components.header.account'))
            ->assertSee(__('components.header.logout'))
            ->assertSee(trans_choice('components.header.cart_count', 2));

        // ── The saved product moves into the cart from the wishlist ──
        $wishlistItem = WishlistItem::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee(
                'action="'.route('wishlist.moveToCart', $wishlistItem).'"',
                false,
            );

        $this->post(route('wishlist.moveToCart', $wishlistItem));

        $this
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee($stand->name)
            ->assertSee(trans_choice('carts.count', 3));

        // ── The checkout, answered step by step and paid on delivery ──
        $this->get(route('checkout.index'))->assertOk();

        $this->walkToReview();

        $placed = $this->post(route('checkout.place'), [
            'checkout_token' => session('checkout.token'),
        ]);

        $order = Order::query()->firstOrFail();

        $placed->assertRedirect($this->confirmationUrl($order));

        // ── The confirmation, then the order in the history ──
        $this
            ->get($this->confirmationUrl($order))
            ->assertOk()
            ->assertSee($order->number)
            ->assertSee(__('orders.confirmation.title'))
            ->assertSee($this->money($order->total_amount_in_fils->toFils()));

        $this
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee($order->number);

        $this
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee($headset->name)
            ->assertSee($stand->name)
            ->assertSee($this->money($order->total_amount_in_fils->toFils()));

        // The cart is spent: the order holds what it took, to be paid on
        // delivery.
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseHas('orders', ['user_id' => $user->id]);
        static::assertSame(
            PaymentMethod::COD,
            $order->payments()->firstOrFail()->method,
        );
    }

    public function test_the_shopper_can_read_the_same_journey_in_arabic(): void
    {
        $this->actingAs($this->account());

        // The switcher is the header's own control, and it works off any page.
        $this
            ->from(route('orders.index'))
            ->post(route('lang'), ['language' => 'ar'])
            ->assertRedirect(route('orders.index'));

        $this
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('lang="ar"', false)
            ->assertSee(__('orders.title', locale: 'ar'));

        $this->post(route('lang'), ['language' => 'en']);

        $this
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('dir="ltr"', false)
            ->assertSee(__('orders.title', locale: 'en'));
    }

    /**
     * Answer every step, which lands the shopper on the review.
     */
    private function walkToReview(): void
    {
        $this->post(route('checkout.customer.store'), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'country_id' => $this->bahrain()->id,
            'phone_number' => '39000000',
        ]);

        $this->post(route('checkout.shipping.store'), [
            'street_address' => '12 Road 1234',
            'city' => 'Manama',
            'state' => 'Capital',
            'country_id' => $this->bahrain()->id,
            'postal_code' => '1234',
        ]);

        $this->post(route('checkout.delivery.store'), [
            'delivery_method' => DeliveryMethod::STANDARD->value,
        ]);

        $this->post(route('checkout.payment.store'), [
            'payment_method' => PaymentMethod::COD->value,
        ]);
    }

    /**
     * The confirmation URL the storefront hands back: the order's number and the
     * access token that opens the page.
     */
    private function confirmationUrl(Order $order): string
    {
        return route('checkout.confirmation', [
            'order' => $order->number,
            'token' => $order->access_token,
        ]);
    }

    /**
     * A fils amount the way the storefront prints it.
     */
    private function money(int $fils): string
    {
        return Money::fromFils($fils)->toBHD();
    }

    private function bahrain(): Country
    {
        return $this->bahrain ??= Country::factory()->bahrain()->create();
    }

    private function account(): User
    {
        return User::factory()->create([
            'name' => 'Layla Hassan',
            'email' => 'layla@example.com',
            'password' => 'password',
        ]);
    }

    private function addToCart(Product $product, int $quantity): TestResponse
    {
        return $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }

    private function asGuest(): static
    {
        return $this->withCookie(config('session.cookie'), self::SESSION_ID);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createProduct(Type $type, array $attributes = []): Product
    {
        return Product::factory()
            ->active()
            ->create(
                array_merge(
                    [
                        'type_id' => $type->id,
                        'stock' => 10,
                        'reserved_stock' => 0,
                        'low_stock_threshold' => 0,
                    ],
                    $attributes,
                ),
            );
    }
}
