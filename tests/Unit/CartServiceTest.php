<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Cart\CartWishlistTransferer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string SESSION_ID = 'CartUnitSession0123456789012345678901234';

    private CartService $cartService;

    private CartWishlistTransferer $transferer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        session()->setId(self::SESSION_ID);

        $this->cartService = app(CartService::class);
        $this->transferer = app(CartWishlistTransferer::class);
    }

    public function test_resolve_creates_a_guest_cart_bound_to_the_session(): void
    {
        $cart = $this->cartService->resolve();

        static::assertNull($cart->user_id);
        static::assertSame(self::SESSION_ID, $cart->session_id);
        static::assertTrue($cart->expires_at->isFuture());
        static::assertEqualsWithDelta(
            CartService::GUEST_LIFETIME_DAYS,
            round(now()->diffInDays($cart->expires_at)),
            1,
        );
    }

    public function test_resolve_creates_a_user_cart_with_the_longer_lifetime(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $cart = $this->cartService->resolve();

        static::assertEquals($user->id, $cart->user_id);
        static::assertNull($cart->session_id);
        static::assertEqualsWithDelta(
            CartService::USER_LIFETIME_DAYS,
            round(now()->diffInDays($cart->expires_at)),
            1,
        );
    }

    public function test_guest_carts_are_extended_to_the_guest_lifetime_on_use(): void
    {
        $cart = Cart::factory()->create([
            'session_id' => self::SESSION_ID,
            'expires_at' => now()->addDay(),
        ]);

        $this->cartService->add($this->createProduct(), 1);

        static::assertEqualsWithDelta(
            CartService::GUEST_LIFETIME_DAYS,
            round(now()->diffInDays($cart->refresh()->expires_at)),
            1,
        );
    }

    public function test_user_carts_are_extended_to_the_account_lifetime_on_use(): void
    {
        $user = User::factory()->create();

        $cart = Cart::factory()
            ->forUser($user)
            ->create([
                'expires_at' => now()->addDay(),
            ]);

        $this->actingAs($user);

        $this->cartService->add($this->createProduct(), 1);

        static::assertEqualsWithDelta(
            CartService::USER_LIFETIME_DAYS,
            round(now()->diffInDays($cart->refresh()->expires_at)),
            1,
        );
    }

    public function test_resolve_reuses_the_existing_cart(): void
    {
        $first = $this->cartService->resolve();
        $second = $this->cartService->resolve();

        static::assertTrue($first->is($second));
        $this->assertDatabaseCount('carts', 1);
    }

    public function test_find_never_creates_a_cart(): void
    {
        static::assertNull($this->cartService->findCart());
        $this->assertDatabaseCount('carts', 0);
    }

    public function test_add_stores_the_current_price_snapshot_and_increments_duplicates(): void
    {
        $product = $this->createProduct(['price_in_fils' => 10_000]);

        $this->cartService->add($product, 2);

        $product->update(['price_in_fils' => 12_000]);
        $this->cartService->add($product, 1);

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 3,
            'price_in_fils' => 12_000,
        ]);
    }

    public function test_add_clamps_to_the_available_stock(): void
    {
        $product = $this->createProduct(['stock' => 3, 'reserved_stock' => 0]);

        $result = $this->cartService->add($product, 5);

        static::assertTrue($result->succeeded());
        static::assertTrue($result->clamped);
        static::assertSame(3, $result->item?->quantity);
    }

    public function test_add_reports_unavailable_for_out_of_stock_products(): void
    {
        $product = $this->createProduct(['stock' => 0, 'reserved_stock' => 0]);

        $result = $this->cartService->add($product, 1);

        static::assertTrue($result->unavailable);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_subtotal_sums_line_totals_in_fils(): void
    {
        $expensive = $this->createProduct(['price_in_fils' => 10_000]);
        $cheap = $this->createProduct(['price_in_fils' => 2_500]);

        $this->cartService->add($expensive, 2);
        $this->cartService->add($cheap, 4);

        static::assertSame(30_000, $this->cartService->subtotalInFils());
    }

    public function test_item_count_and_is_empty_reflect_the_cart(): void
    {
        static::assertTrue($this->cartService->isEmpty());
        static::assertSame(0, $this->cartService->itemCount());

        $product = $this->createProduct();

        $this->cartService->add($product, 3);

        static::assertFalse($this->cartService->isEmpty());
        static::assertSame(3, $this->cartService->itemCount());
    }

    public function test_clear_empties_the_cart_but_keeps_it(): void
    {
        $this->cartService->add($this->createProduct(), 2);

        $this->cartService->clear();

        static::assertTrue($this->cartService->isEmpty());
        $this->assertDatabaseCount('carts', 1);
    }

    public function test_move_all_to_wishlist_moves_every_line(): void
    {
        $first = $this->createProduct();
        $second = $this->createProduct();

        $this->cartService->add($first, 2);
        $this->cartService->add($second, 1);

        $result = $this->transferer->moveAllToWishlist();

        static::assertSame(2, $result->moved);
        static::assertSame(0, $result->kept);
        static::assertSame(2, $result->total());
        static::assertFalse($result->isEmpty());
        static::assertTrue($this->cartService->isEmpty());
        $this->assertDatabaseCount('wishlist_items', 2);
    }

    public function test_move_all_to_wishlist_keeps_a_line_whose_product_is_gone(): void
    {
        $gone = $this->createProduct();
        $available = $this->createProduct();

        $this->cartService->add($gone, 1);
        $this->cartService->add($available, 1);

        $gone->delete();

        $result = $this->transferer->moveAllToWishlist();

        static::assertSame(1, $result->moved);
        static::assertSame(1, $result->kept);
        $this->assertDatabaseHas('cart_items', ['product_id' => $gone->id]);
        $this->assertDatabaseMissing('cart_items', [
            'product_id' => $available->id,
        ]);
        $this->assertDatabaseCount('wishlist_items', 1);
    }

    public function test_move_all_to_wishlist_is_empty_without_a_cart(): void
    {
        $result = $this->transferer->moveAllToWishlist();

        static::assertTrue($result->isEmpty());
        static::assertSame(0, $result->total());
    }

    public function test_merge_folds_the_guest_cart_into_the_user_cart(): void
    {
        $product = $this->createProduct(['price_in_fils' => 10_000]);

        $this->cartService->add($product, 2);

        $user = User::factory()->create();

        $this->cartService->merge($user);

        $userCart = Cart::query()->where('user_id', $user->id)->firstOrFail();

        static::assertNull($userCart->session_id);
        static::assertEqualsWithDelta(
            CartService::USER_LIFETIME_DAYS,
            round(now()->diffInDays($userCart->expires_at)),
            1,
        );
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $userCart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseCount('carts', 1);
    }

    public function test_merge_combines_quantities_and_clamps_to_stock(): void
    {
        $product = $this->createProduct([
            'price_in_fils' => 10_000,
            'stock' => 5,
            'reserved_stock' => 0,
        ]);

        $user = User::factory()->create();
        $userCart = Cart::factory()->forUser($user)->create();

        CartItem::factory()
            ->forCart($userCart)
            ->forProduct($product)
            ->create(['quantity' => 4, 'price_in_fils' => 10_000]);

        $this->cartService->add($product, 3);

        $this->cartService->merge($user);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $userCart->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
        $this->assertDatabaseCount('carts', 1);
    }

    public function test_revalidate_clamps_a_line_to_what_is_left_and_re_prices_it(): void
    {
        $product = $this->createProduct([
            'price_in_fils' => 10_000,
            'stock' => 5,
        ]);

        $this->cartService->add($product, 4);

        // The shelf is picked over — and the price moves with it — while the
        // line sits in the cart.
        $product->update(['stock' => 2, 'price_in_fils' => 12_000]);

        $this->cartService->revalidate();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'price_in_fils' => 12_000,
        ]);
    }

    public function test_revalidate_leaves_a_line_whose_product_is_gone_alone(): void
    {
        $product = $this->createProduct();

        $this->cartService->add($product, 2);

        $product->delete();

        // There is nothing to clamp it to: the line stays where the shopper can
        // see what happened to it and remove it.
        $this->cartService->revalidate();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_revalidate_without_a_cart_does_nothing(): void
    {
        $this->cartService->revalidate();

        $this->assertDatabaseCount('carts', 0);
    }

    public function test_prune_expired_removes_stale_carts(): void
    {
        $expired = Cart::factory()->expired()->create();
        $active = Cart::factory()->create();

        $this->cartService->pruneExpired();

        $this->assertDatabaseMissing('carts', ['id' => $expired->id]);
        $this->assertDatabaseHas('carts', ['id' => $active->id]);
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
}
