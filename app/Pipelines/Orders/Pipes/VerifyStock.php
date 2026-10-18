<?php

declare(strict_types=1);

namespace App\Pipelines\Orders\Pipes;

use App\DTOs\CartLineData;
use App\DTOs\CartSummary;
use App\DTOs\OrderPlacementResult;
use App\DTOs\OrderProcessingContext;
use App\Models\Product;
use Closure;
use Illuminate\Support\Collection;

/**
 * A line that cannot be filled stops the placement here: the context is handed
 * back with the "stock changed" result and the rest of the chain is skipped, so
 * nothing is written and the cart is what the shopper has to look at.
 */
class VerifyStock
{
    public function handle(
        OrderProcessingContext $context,
        Closure $next,
    ): OrderProcessingContext {
        if (! $this->stockHasMoved($context->cartOrFail(), $context->stock->lockedProducts)) {
            return $next($context->recordPipe(self::class));
        }

        return $context->withResult(OrderPlacementResult::stockChanged())->recordPipe(self::class);
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function stockHasMoved(
        CartSummary $cart,
        Collection $products,
    ): bool {
        foreach ($cart->lines as $line) {
            $product = $this->lockedProduct($line, $products);

            if (
                $product === null
                || ! $product->isActive()
                || $product->getAvailableStock() < $line->quantity
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function lockedProduct(
        CartLineData $line,
        Collection $products,
    ): ?Product {
        $productId = $line->product?->getKey();

        return $productId === null ? null : $products->get($productId);
    }
}
