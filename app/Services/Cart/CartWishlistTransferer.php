<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\DTOs\CartBulkMoveResult;
use App\DTOs\CartItemResult;
use App\DTOs\WishlistBulkMoveResult;
use App\DTOs\WishlistItemResult;
use App\Models\CartItem;
use App\Models\WishlistItem;
use App\Services\Wishlist\WishlistService;
use Illuminate\Support\Facades\DB;

/**
 * The moves between the cart and the wishlist. Neither list owns the other's
 * rows, so the operations that touch both — and would otherwise make the two
 * services depend on each other — live here, where both are injected plainly
 * rather than one being reached for out of the container.
 *
 * A line or entry whose product left the catalogue is left where it is, because
 * the list it would move to has no product to reference.
 */
final class CartWishlistTransferer
{
    public function __construct(
        private CartService $cartService,
        private WishlistService $wishlistService,
        private CartResolver $cartResolver,
    ) {}

    public function moveToWishlist(CartItem $item): WishlistItemResult
    {
        $cart = $this->cartResolver->assertOwned($item);
        $product = $item->product;

        if ($product === null) {
            return WishlistItemResult::unavailable();
        }

        return DB::transaction(function () use ($item, $cart, $product): WishlistItemResult {
            $result = $this->wishlistService->add($product);

            $item->delete();

            $this->cartService->forgetCartItems();

            $this->cartResolver->touch($cart);

            return $result;
        });
    }

    /**
     * Move every line onto the wishlist in one go. Each line goes through the
     * same rule as the single-line move, so one whose product is gone stays in
     * the cart and is counted as kept rather than lost.
     */
    public function moveAllToWishlist(): CartBulkMoveResult
    {
        $items = $this->cartService->cartItems();

        $moved = 0;
        $kept = 0;

        foreach ($items as $item) {
            if ($this->moveToWishlist($item)->unavailable) {
                $kept++;

                continue;
            }

            $moved++;
        }

        return CartBulkMoveResult::fromCounts($moved, $kept);
    }

    /**
     * Move an entry to the cart: the current product data is re-read (only the
     * reference was stored) and the entry is dropped once the product reached
     * the cart. An unavailable product stays on the list so nothing is lost.
     */
    public function moveToCart(
        WishlistItem $item,
        int $quantity = 1,
    ): CartItemResult {
        $this->wishlistService->assertOwned($item);

        $product = $item->product;

        if ($product === null) {
            return CartItemResult::unavailable();
        }

        $result = $this->cartService->add($product, $quantity);

        if ($result->succeeded()) {
            $this->wishlistService->remove($item);
        }

        return $result;
    }

    /**
     * Move every saved entry into the cart in one go. Each entry goes through
     * the same rule as the single-entry move, so one whose product cannot reach
     * the cart stays saved and is counted rather than dropped.
     */
    public function moveAllToCart(): WishlistBulkMoveResult
    {
        $moved = 0;
        $clamped = 0;
        $unavailable = 0;

        foreach ($this->wishlistService->items() as $item) {
            $result = $this->moveToCart($item);

            if ($result->unavailable) {
                $unavailable++;

                continue;
            }

            $moved++;

            if ($result->clamped) {
                $clamped++;
            }
        }

        return WishlistBulkMoveResult::fromCounts(
            $moved,
            $clamped,
            $unavailable,
        );
    }
}
