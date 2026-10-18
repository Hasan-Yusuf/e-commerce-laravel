<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\DTOs\CartItemResult;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\Catalog\ProductPricingService;
use Illuminate\Support\Facades\DB;

/**
 * The stock-validated writes to the open cart: adding, re-quantifying and
 * removing a line, each storing a price snapshot and refreshing the cart's
 * expiry.
 */
final class CartMutator
{
    public function __construct(
        private CartResolver $cartResolver,
        private ProductPricingService $pricingService,
    ) {}

    /**
     * Add a product to the cart. A product already in the cart has its line
     * quantity incremented, and every quantity is clamped to the available
     * stock (the result reports when clamping happened).
     */
    public function add(Product $product, int $quantity): CartItemResult
    {
        $requested = max(1, $quantity);
        $available = $product->getAvailableStock();

        // Refused before a cart is resolved: a shopper who cannot add anything
        // should not be left holding an empty cart for having tried.
        if (! $product->isActive() || $available <= 0) {
            return CartItemResult::unavailable();
        }

        $cart = $this->cartResolver->current();

        return DB::transaction(function () use ($product, $requested, $available, $cart): CartItemResult {
            /** @var CartItem $item */
            $item = $cart->cartItems()->firstOrNew([
                'product_id' => $product->getKey(),
            ]);

            /** @var int|null $quantity */
            $quantity = $item->quantity;

            $desired = ($quantity ?? 0) + $requested;
            $applied = min($desired, $available);
            $priceInFils = $this->pricingService->getCurrentPrice($product);

            $item->fill([
                'quantity' => $applied,
                'price_in_fils' => $priceInFils,
            ])->save();

            $this->cartResolver->touch($cart);

            return CartItemResult::accepted($item, $applied < $desired);
        });
    }

    /**
     * Set a line to a quantity (clamped to the available stock). The item must
     * belong to the current cart; anything else is a 404.
     */
    public function update(CartItem $item, int $quantity): CartItemResult
    {
        $cart = $this->cartResolver->assertOwned($item);
        $requested = max(1, $quantity);

        return DB::transaction(function () use ($cart, $item, $requested): CartItemResult {
            $product = $item->product;
            $available = $product === null ? 0 : $product->getAvailableStock();

            if ($product === null || ! $product->isActive() || $available <= 0) {
                return CartItemResult::unavailable($item);
            }

            $applied = min($requested, $available);

            $item->update([
                'quantity' => $applied,
                'price_in_fils' => $this->pricingService->getCurrentPrice(
                    $product,
                ),
            ]);

            $this->cartResolver->touch($cart);

            return CartItemResult::accepted($item, $applied < $requested);
        });
    }

    /**
     * Remove one line. The item must belong to the current cart.
     */
    public function remove(CartItem $item): void
    {
        $cart = $this->cartResolver->assertOwned($item);

        $item->delete();

        $this->cartResolver->touch($cart);
    }

    /**
     * Empty the current cart without deleting it.
     */
    public function clear(): void
    {
        $cart = $this->cartResolver->find();

        if ($cart === null) {
            return;
        }

        $cart->cartItems()->delete();

        $this->cartResolver->touch($cart);
    }
}
