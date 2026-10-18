<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\FlashMessage;
use App\Enums\CheckoutStep;
use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use App\Models\Cart;
use App\Models\Country;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Payments\CodGateway;
use App\Services\Payments\PaymentGateway;
use App\Support\Money;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

/**
 * The edges of the shopping flow: registering halfway through a checkout, a
 * price and a stock level that moved between the cart and the review, products
 * that leave the catalogue, an expired cart, a post with nowhere to return to,
 * and an order that cannot be written at all.
 */
class CheckoutEdgeCasesTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * A fixed, valid session id (40 alphanumeric chars) so guest requests within
     * a test share one cart and one set of checkout answers. The test client does
     * not replay the session cookie automatically, so it is sent with every
     * request instead.
     */
    private const string SESSION_ID = 'CheckoutEdgeCasesTest0123456789012345678';

    /**
     * The country the flow's phone numbers are given for, made once per test.
     */
    private ?Country $bahrain = null;

    public function test_a_guest_who_registers_mid_checkout_keeps_the_answers_and_places_the_order(): void
    {
        $product = $this->createProduct(['stock' => 10]);

        $this->addToCart($product, 2);
        $this->walkToReview();

        // The review is where a guest registers from, so it is where they have to
        // come back to.
        $this->asGuest()->get(CheckoutStep::REVIEW->url())->assertOk();

        $this->stubBreachCheck();

        $this
            ->asGuest()
            ->post(route('register.store'), [
                'name' => 'Layla Hassan',
                'email' => 'layla@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(CheckoutStep::REVIEW->url());

        $user = User::query()
            ->where('email', 'layla@example.com')
            ->firstOrFail();

        // The guest cart came onto the account, and the shopper is no longer a
        // guest on the review.
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseMissing('carts', ['session_id' => self::SESSION_ID]);

        $this
            ->asGuest()
            ->get(CheckoutStep::REVIEW->url())
            ->assertOk()
            ->assertSee($product->name)
            ->assertDontSee(__('checkout.place.guest_notice'));

        // The answers survived the round trip, so the order places from here.
        $this
            ->asGuest()
            ->post(route('checkout.place'), [
                'checkout_token' => session('checkout.token'),
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('orders', 1);
        static::assertSame(
            $user->id,
            Order::query()->firstOrFail()->user_id,
        );
    }

    public function test_a_price_change_between_the_cart_and_the_checkout_is_shown_on_the_review(): void
    {
        $product = $this->createProduct(['price_in_fils' => 10_000]);

        $this->addToCart($product, 2);

        // The price moves while the line sits in the cart.
        $product->update(['price_in_fils' => 8_000]);

        $notice = $this->priceChangedNotice(10_000, 8_000);

        // The cart says so...
        $this
            ->asGuest()
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee($notice);

        $this->walkToReview();

        // ...and so does the review, whose total is the price the order would be
        // placed at rather than the one the line was added at.
        $this
            ->asGuest()
            ->get(CheckoutStep::REVIEW->url())
            ->assertOk()
            ->assertSee($notice)
            ->assertSee($this->money(16_000));
    }

    public function test_a_stock_drop_leaves_the_cart_line_flagged_as_unfillable(): void
    {
        $product = $this->createProduct(['stock' => 5]);

        $this->addToCart($product, 4);

        // Most of it sells while the line sits in the cart.
        $product->update(['stock' => 2]);

        $this
            ->asGuest()
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee(__('carts.item.exceeds_stock', ['count' => 2]));

        // Nothing was rewritten behind the shopper's back: the cart is where the
        // line is flagged, and placing the order is what refuses it.
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 4,
        ]);
    }

    public function test_adding_an_out_of_stock_product_is_refused_without_leaving_a_cart(): void
    {
        $product = $this->createProduct(['stock' => 0]);

        $this
            ->asGuest()
            ->post(route('cart.add'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas(
                FlashMessage::TITLE,
                __('carts.notify.unavailable_title'),
            );

        // The refusal costs the shopper nothing: no line, and no empty cart left
        // behind for having tried.
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseCount('carts', 0);
    }

    public function test_adding_a_product_that_is_no_longer_sold_is_refused_gracefully(): void
    {
        $product = $this->createProduct();
        $product->delete();

        $this
            ->asGuest()
            ->post(route('cart.add'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('product_id');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_an_impossible_quantity_never_reaches_the_cart(): void
    {
        $product = $this->createProduct(['stock' => 10]);

        foreach ([0, -1, 1000, 'many'] as $quantity) {
            $this
                ->asGuest()
                ->post(route('cart.add'), [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ])
                ->assertSessionHasErrors('quantity');
        }

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_an_expired_guest_cart_is_pruned_and_the_shopper_starts_a_fresh_one(): void
    {
        $product = $this->createProduct(['stock' => 5]);

        $this->addToCart($product, 2);

        $cart = Cart::query()
            ->where('session_id', self::SESSION_ID)
            ->firstOrFail();

        $cart->update(['expires_at' => now()->subDay()]);

        $this->artisan('carts:prune')->assertSuccessful();

        $this->assertDatabaseMissing('carts', ['id' => $cart->id]);
        $this->assertDatabaseCount('cart_items', 0);

        // The session is still the shopper's: the next add builds a new cart
        // rather than resurrecting the pruned one.
        $this->addToCart($product, 3);

        $fresh = Cart::query()
            ->where('session_id', self::SESSION_ID)
            ->firstOrFail();

        static::assertNotSame($cart->id, $fresh->id);
        static::assertTrue($fresh->expires_at->isFuture());
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $fresh->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_an_order_that_cannot_be_written_leaves_the_cart_and_the_stock_alone(): void
    {
        // The payment cannot be recorded, which fails the placement after the
        // order and its lines have already been written inside the transaction.
        $this->app->bind(CodGateway::class, static fn (): PaymentGateway => new class implements PaymentGateway
        {
            #[\Override]
            public function method(): PaymentMethod
            {
                return PaymentMethod::COD;
            }

            #[\Override]
            public function createPaymentFor(Order $order): Payment
            {
                throw new RuntimeException('The payment could not be recorded.');
            }
        });

        $this->actingAs(User::factory()->create());
        $product = $this->createProduct(['stock' => 10]);

        $this->addToCart($product, 2);
        $this->walkToReview();

        $failureCaught = false;

        try {
            $this
                ->withoutExceptionHandling()
                ->asGuest()
                ->post(
                    route('checkout.place'),
                    ['checkout_token' => session('checkout.token')],
                );

            static::fail('The placement was expected to fail.');
        } catch (RuntimeException) {
            // Expected: the failure surfaces and the transaction rolls back.
            $failureCaught = true;
        }

        static::assertTrue($failureCaught);

        // Nothing is half-written, and the stock the lines briefly reserved is
        // released with them.
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('payments', 0);
        static::assertSame(0, (int) $product->refresh()->reserved_stock);

        // The shopper is left where they were, with the cart and the answers.
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        static::assertNotNull(session('checkout'));
    }

    public function test_a_post_with_no_referer_lands_on_a_page_rather_than_nowhere(): void
    {
        $product = $this->createProduct();

        // The cart mutations fall back to the cart...
        $this
            ->asGuest()
            ->post(route('cart.add'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertRedirect(route('cart.index'));

        // ...and switching the language falls back to the home page.
        $this
            ->asGuest()
            ->post(route('lang'), ['language' => 'ar'])
            ->assertRedirect(route('home'));
    }

    /**
     * The review's own wording for a line whose price moved.
     */
    private function priceChangedNotice(int $old, int $new): string
    {
        return __('carts.item.price_changed', [
            'old' => $this->money($old),
            'new' => $this->money($new),
        ]);
    }

    /**
     * A fils amount the way the storefront prints it.
     */
    private function money(int $fils): string
    {
        return Money::fromFils($fils)->toBHD();
    }

    /**
     * Answer every step, which lands the shopper on the review.
     */
    private function walkToReview(): void
    {
        $this->asGuest()->post(route('checkout.customer.store'), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'country_id' => $this->bahrain()->id,
            'phone_number' => '39000000',
        ]);

        $this->asGuest()->post(route('checkout.delivery.store'), [
            'delivery_method' => DeliveryMethod::STANDARD->value,
        ]);

        $this->asGuest()->post(route('checkout.shipping.store'), [
            'street_address' => '12 Road 1234',
            'city' => 'Manama',
            'state' => 'Capital',
            'country_id' => $this->bahrain()->id,
            'postal_code' => '1234',
        ]);

        $this->asGuest()->post(route('checkout.payment.store'), [
            'payment_method' => PaymentMethod::COD->value,
        ]);
    }

    private function bahrain(): Country
    {
        return $this->bahrain ??= Country::factory()->bahrain()->create();
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
    private function createProduct(array $attributes = []): Product
    {
        return Product::factory()
            ->active()
            ->create(
                array_merge(
                    [
                        'stock' => 10,
                        'reserved_stock' => 0,
                        'low_stock_threshold' => 0,
                    ],
                    $attributes,
                ),
            );
    }

    /**
     * Replace the breach lookup with a stub, so the suite never calls out.
     */
    private function stubBreachCheck(): void
    {
        $this->app->bind(
            UncompromisedVerifier::class,
            static fn (): UncompromisedVerifier => new class implements UncompromisedVerifier
            {
                #[\Override]
                public function verify($data): bool
                {
                    return true;
                }
            },
        );
    }
}
