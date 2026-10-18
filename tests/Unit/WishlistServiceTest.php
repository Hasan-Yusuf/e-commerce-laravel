<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use App\Services\Cart\CartService;
use App\Services\Cart\CartWishlistTransferer;
use App\Services\Wishlist\WishlistService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class WishlistServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string SESSION_ID = 'WishlistUnitSession012345678901234567890';

    private WishlistService $wishlistService;

    private CartWishlistTransferer $transfer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        session()->setId(self::SESSION_ID);

        $this->wishlistService = app(WishlistService::class);
        $this->transfer = app(CartWishlistTransferer::class);
    }

    public function test_add_saves_a_product_for_the_guest_session(): void
    {
        $product = $this->createProduct();

        $result = $this->wishlistService->add($product);

        static::assertTrue($result->succeeded());
        static::assertFalse($result->alreadyExists);
        $this->assertDatabaseHas('wishlist_items', [
            'product_id' => $product->id,
            'session_id' => self::SESSION_ID,
            'user_id' => null,
        ]);
    }

    public function test_add_saves_against_the_signed_in_account(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $this->actingAs($user);

        $this->wishlistService->add($product);

        $this->assertDatabaseHas('wishlist_items', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'session_id' => null,
        ]);
    }

    public function test_add_is_idempotent(): void
    {
        $product = $this->createProduct();

        $first = $this->wishlistService->add($product);
        $second = $this->wishlistService->add($product);

        static::assertFalse($first->alreadyExists);
        static::assertTrue($second->alreadyExists);
        static::assertTrue($second->succeeded());
        static::assertSame($first->item?->id, $second->item?->id);
        $this->assertDatabaseCount('wishlist_items', 1);
    }

    public function test_contains_and_find_report_the_saved_products(): void
    {
        $saved = $this->createProduct();
        $other = $this->createProduct();

        $this->wishlistService->add($saved);

        static::assertTrue($this->wishlistService->contains($saved));
        static::assertFalse($this->wishlistService->contains($other));
        static::assertSame(
            $saved->id,
            $this->wishlistService->findItemForProduct($saved)?->product_id,
        );
        static::assertNull($this->wishlistService->findItemForProduct($other));
    }

    public function test_count_and_is_empty_reflect_the_list(): void
    {
        static::assertTrue($this->wishlistService->isEmpty());
        static::assertSame(0, $this->wishlistService->count());

        $this->wishlistService->add($this->createProduct());

        static::assertFalse($this->wishlistService->isEmpty());
        static::assertSame(1, $this->wishlistService->count());
    }

    public function test_a_mutation_is_visible_after_a_cached_read(): void
    {
        $product = $this->createProduct();

        // Prime the request cache with an empty list...
        static::assertTrue($this->wishlistService->isEmpty());

        // ...then prove the write invalidates it.
        $this->wishlistService->add($product);

        static::assertTrue($this->wishlistService->contains($product));
        static::assertSame(1, $this->wishlistService->count());
    }

    public function test_the_cached_list_reloads_when_the_owner_changes(): void
    {
        $this->wishlistService->add($this->createProduct());

        static::assertSame(1, $this->wishlistService->count());

        $this->actingAs(User::factory()->create());

        static::assertSame(0, $this->wishlistService->count());
    }

    public function test_remove_deletes_the_entry(): void
    {
        $product = $this->createProduct();

        $this->wishlistService->add($product);

        $item = WishlistItem::query()->firstOrFail();

        $this->wishlistService->remove($item);

        $this->assertDatabaseCount('wishlist_items', 0);
        static::assertFalse($this->wishlistService->contains($product));
    }

    public function test_remove_rejects_an_entry_owned_by_another_list(): void
    {
        $item = WishlistItem::factory()
            ->forSession('AnotherWishlistUnitSession012345678901234')
            ->forProduct($this->createProduct())
            ->create();

        $this->expectException(NotFoundHttpException::class);

        $this->wishlistService->remove($item);
    }

    public function test_move_to_cart_re_reads_the_current_product_data(): void
    {
        $product = $this->createProduct(['price_in_fils' => 10_000]);

        $this->wishlistService->add($product);

        $item = WishlistItem::query()->firstOrFail();

        // The wishlist stores only the reference, so the move must pick up the
        // price as it stands now rather than a stale snapshot.
        $product->update(['price_in_fils' => 12_000]);

        $result = $this->transfer->moveToCart($item);

        static::assertTrue($result->succeeded());
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'price_in_fils' => 12_000,
        ]);
        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_move_to_cart_clamps_to_the_available_stock(): void
    {
        $product = $this->createProduct(['stock' => 2, 'reserved_stock' => 0]);

        $this->wishlistService->add($product);

        $item = WishlistItem::query()->firstOrFail();

        $result = $this->transfer->moveToCart($item, 5);

        static::assertTrue($result->clamped);
        static::assertSame(2, $result->item?->quantity);
        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_move_to_cart_keeps_the_entry_when_the_product_is_unavailable(): void
    {
        $product = $this->createProduct(['stock' => 0, 'reserved_stock' => 0]);

        $this->wishlistService->add($product);

        $item = WishlistItem::query()->firstOrFail();

        $result = $this->transfer->moveToCart($item);

        static::assertTrue($result->unavailable);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseHas('wishlist_items', ['id' => $item->id]);
    }

    public function test_move_to_cart_rejects_an_entry_owned_by_another_list(): void
    {
        $item = WishlistItem::factory()
            ->forSession('AnotherWishlistUnitSession012345678901234')
            ->forProduct($this->createProduct())
            ->create();

        $this->expectException(NotFoundHttpException::class);

        $this->transfer->moveToCart($item);
    }

    public function test_move_all_to_cart_moves_every_entry(): void
    {
        $first = $this->createProduct();
        $second = $this->createProduct();

        $this->wishlistService->add($first);
        $this->wishlistService->add($second);

        $result = $this->transfer->moveAllToCart();

        static::assertSame(2, $result->moved);
        static::assertSame(0, $result->clamped);
        static::assertSame(0, $result->unavailable);
        static::assertSame(2, $result->total());
        static::assertTrue($this->wishlistService->isEmpty());
        $this->assertDatabaseCount('cart_items', 2);
    }

    public function test_move_all_to_cart_keeps_an_unavailable_entry(): void
    {
        $available = $this->createProduct();
        $soldOut = $this->createProduct(['stock' => 0, 'reserved_stock' => 0]);

        $this->wishlistService->add($available);
        $this->wishlistService->add($soldOut);

        $result = $this->transfer->moveAllToCart();

        static::assertSame(1, $result->moved);
        static::assertSame(1, $result->unavailable);
        static::assertSame(2, $result->total());
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $available->id,
        ]);
        $this->assertDatabaseHas('wishlist_items', [
            'product_id' => $soldOut->id,
        ]);
    }

    public function test_move_all_to_cart_counts_an_entry_trimmed_to_stock(): void
    {
        $product = $this->createProduct(['stock' => 1, 'reserved_stock' => 0]);

        // The cart already holds the only unit, so the move cannot add another.
        app(CartService::class)->add($product, 1);

        $this->wishlistService->add($product);

        $result = $this->transfer->moveAllToCart();

        static::assertSame(1, $result->moved);
        static::assertSame(1, $result->clamped);
        static::assertSame(1, $result->total());
        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_move_all_to_cart_is_empty_without_saved_items(): void
    {
        $result = $this->transfer->moveAllToCart();

        static::assertTrue($result->isEmpty());
        static::assertSame(0, $result->total());
    }

    public function test_merge_folds_the_guest_list_into_the_user_list(): void
    {
        $product = $this->createProduct();

        $this->wishlistService->add($product);

        $user = User::factory()->create();

        $this->wishlistService->merge($user);

        $this->assertDatabaseCount('wishlist_items', 1);
        $this->assertDatabaseHas('wishlist_items', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'session_id' => null,
        ]);
    }

    public function test_merge_drops_the_guest_copy_of_an_already_saved_product(): void
    {
        $product = $this->createProduct();
        $user = User::factory()->create();

        WishlistItem::factory()->forUser($user)->forProduct($product)->create();

        $this->wishlistService->add($product);

        $this->wishlistService->merge($user);

        $this->assertDatabaseCount('wishlist_items', 1);
        $this->assertDatabaseHas('wishlist_items', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'session_id' => null,
        ]);
    }

    public function test_merge_keeps_the_accounts_own_items(): void
    {
        $accountProduct = $this->createProduct();
        $guestProduct = $this->createProduct();
        $user = User::factory()->create();

        WishlistItem::factory()
            ->forUser($user)
            ->forProduct($accountProduct)
            ->create();

        $this->wishlistService->add($guestProduct);

        $this->wishlistService->merge($user);

        $this->assertDatabaseCount('wishlist_items', 2);
        $this->assertDatabaseHas('wishlist_items', [
            'product_id' => $accountProduct->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('wishlist_items', [
            'product_id' => $guestProduct->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_merge_does_nothing_without_guest_items(): void
    {
        $user = User::factory()->create();

        $this->wishlistService->merge($user);

        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_lines_expose_the_card_data_of_saved_products(): void
    {
        $product = $this->createProduct(['price_in_fils' => 10_000]);

        $this->wishlistService->add($product);

        $lines = $this->wishlistService->lines();

        static::assertCount(1, $lines);

        $line = $lines->first();

        static::assertSame($product->id, $line?->item?->product_id);
        static::assertFalse($line->isGone());
        static::assertTrue($product->is($line->product));
        static::assertSame(10_000, $line->card?->currentPrice->toFils());
        static::assertSame($product->id, $line->card->product->id);
    }

    public function test_lines_keep_a_gone_product_as_a_removable_entry(): void
    {
        $product = $this->createProduct();

        $this->wishlistService->add($product);

        $product->delete();

        $line = $this->wishlistService->lines()->first();

        static::assertTrue($line->isGone());
        static::assertNull($line?->product);
        static::assertNull($line?->card);
        static::assertInstanceOf(WishlistItem::class, $line?->item);
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
