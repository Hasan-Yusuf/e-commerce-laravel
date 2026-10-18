<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * What becomes of a guest's cart and wishlist when they sign in: the two lists
 * are folded into the account's — combined rather than duplicated, clamped to
 * what can actually be bought, and emptied of the guest rows they came from.
 */
class CartWishlistMergeTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * A fixed, valid session id (40 alphanumeric chars) so guest requests within
     * a test share one cart and one wishlist. The test client does not replay the
     * session cookie automatically, so it is sent with every request instead.
     */
    private const string SESSION_ID = 'CartWishlistMergeTest0123456789012345678';

    public function test_signing_in_combines_the_guest_cart_with_the_accounts_own(): void
    {
        $user = $this->account();
        $shared = $this->createProduct(['stock' => 10]);
        $accountOnly = $this->createProduct(['stock' => 10]);
        $guestOnly = $this->createProduct(['stock' => 10]);

        $this->accountCartHolding($user, [
            [$shared, 2],
            [$accountOnly, 1],
        ]);

        $this->addToGuestCart($shared, 3);
        $this->addToGuestCart($guestOnly, 1);

        $this->signIn($user);

        $cart = Cart::query()->where('user_id', $user->id)->firstOrFail();

        // The product both lists held is one line of 2 + 3 rather than two lines;
        // the products only one list held are carried over untouched.
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $shared->id,
            'quantity' => 5,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $accountOnly->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $guestOnly->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseCount('cart_items', 3);

        // One cart remains, and it is the account's: the guest's is gone.
        $this->assertDatabaseCount('carts', 1);
        $this->assertDatabaseMissing('carts', ['session_id' => self::SESSION_ID]);
    }

    public function test_signing_in_clamps_the_combined_quantity_to_the_stock(): void
    {
        $user = $this->account();
        $product = $this->createProduct(['stock' => 4]);

        $this->accountCartHolding($user, [[$product, 3]]);
        $this->addToGuestCart($product, 3);

        $this->signIn($user);

        // 3 + 3 would be 6, but only 4 can be ordered, so the merged line holds
        // what the stock can fill.
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 4,
        ]);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_signing_in_drops_the_guest_line_whose_product_is_gone(): void
    {
        $user = $this->account();
        $gone = $this->createProduct();
        $kept = $this->createProduct();

        $this->addToGuestCart($gone, 1);
        $this->addToGuestCart($kept, 1);

        // The product leaves the catalogue while the shopper is still a guest.
        $gone->update(['activated_at' => null]);

        $this->signIn($user);

        // Nothing references a product that cannot be bought any more; the rest
        // of the cart is carried over.
        $this->assertDatabaseMissing('cart_items', ['product_id' => $gone->id]);
        $this->assertDatabaseHas('cart_items', ['product_id' => $kept->id]);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_signing_in_merges_the_wishlist_without_duplicates(): void
    {
        $user = $this->account();
        $shared = $this->createProduct();
        $accountOnly = $this->createProduct();
        $guestOnly = $this->createProduct();

        WishlistItem::factory()->forUser($user)->forProduct($shared)->create();
        WishlistItem::factory()->forUser($user)->forProduct($accountOnly)->create();

        $this->saveToGuestWishlist($shared);
        $this->saveToGuestWishlist($guestOnly);

        $this->signIn($user);

        // The product both lists held is saved on the account once, not twice.
        static::assertSame(
            1,
            WishlistItem::query()
                ->where('user_id', $user->id)
                ->where('product_id', $shared->id)
                ->count(),
        );
        static::assertSame(
            3,
            WishlistItem::query()->where('user_id', $user->id)->count(),
        );
        $this->assertDatabaseCount('wishlist_items', 3);
        $this->assertDatabaseMissing('wishlist_items', [
            'session_id' => self::SESSION_ID,
        ]);
    }

    public function test_registering_carries_the_guest_cart_and_wishlist_onto_the_new_account(): void
    {
        $product = $this->createProduct(['stock' => 5]);

        $this->addToGuestCart($product, 2);
        $this->saveToGuestWishlist($product);

        $this->stubBreachCheck();

        $this
            ->asGuest()
            ->post(route('register.store'), [
                'name' => 'Layla Hassan',
                'email' => 'layla@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('home'));

        $user = User::query()
            ->where('email', 'layla@example.com')
            ->firstOrFail();

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'session_id' => null,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('wishlist_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'session_id' => null,
        ]);

        // The guest's rows are gone with the session that owned them.
        $this->assertDatabaseMissing('carts', ['session_id' => self::SESSION_ID]);
        $this->assertDatabaseMissing('wishlist_items', [
            'session_id' => self::SESSION_ID,
        ]);
    }

    private function account(): User
    {
        return User::factory()->create(['password' => 'password']);
    }

    /**
     * Give the account a cart it already had, with one line per product.
     *
     * @param  array<int, array{0: Product, 1: int}>  $lines
     */
    private function accountCartHolding(User $user, array $lines): void
    {
        $cart = Cart::factory()->forUser($user)->create();

        foreach ($lines as [$product, $quantity]) {
            CartItem::factory()
                ->forCart($cart)
                ->forProduct($product)
                ->create([
                    'quantity' => $quantity,
                    'price_in_fils' => 10_000,
                ]);
        }
    }

    private function signIn(User $user): void
    {
        $this
            ->asGuest()
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('home'));
    }

    private function addToGuestCart(
        Product $product,
        int $quantity,
    ): TestResponse {
        return $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }

    private function saveToGuestWishlist(Product $product): TestResponse
    {
        return $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $product->id,
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
