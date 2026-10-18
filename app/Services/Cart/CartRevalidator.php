<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Models\CartItem;
use App\Services\Catalog\ProductPricingService;
use Illuminate\Support\Facades\DB;

/**
 * Brings every cart line back in line with what is actually orderable after a
 * refused placement, so the shopper is returned to a cart they can order.
 */
final class CartRevalidator
{
    public function __construct(
        private CartResolver $cartResolver,
        private ProductPricingService $pricingService,
    ) {}

    /**
     * A line whose product sold down while it sat in the cart is clamped to
     * what is left, and every line is re-priced to the current price. A line
     * whose product was removed or deactivated is left alone — there is nothing
     * to clamp it to, and the cart page is where it is flagged and removed.
     */
    public function revalidate(): void
    {
        $cart = $this->cartResolver->find();

        if ($cart === null) {
            return;
        }

        DB::transaction(function () use ($cart): void {
            foreach ($cart->cartItems()->with('product')->get() as $item) {
                /** @var CartItem $item */
                $product = $item->product;
                $available = $product === null ? 0 : $product->getAvailableStock();

                if (
                    $product === null
                    || ! $product->isActive()
                    || $available <= 0
                ) {
                    continue;
                }

                $item->update([
                    'quantity' => min((int) $item->quantity, $available),
                    'price_in_fils' => $this->pricingService->getCurrentPrice(
                        $product,
                    ),
                ]);
            }

            $this->cartResolver->touch($cart);
        });
    }
}
