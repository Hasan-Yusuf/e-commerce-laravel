<?php

declare(strict_types=1);

namespace App\Pipelines\Orders\Pipes;

use App\DTOs\CartLineData;
use App\DTOs\OrderProcessingContext;
use App\Models\Product;
use Closure;
use Illuminate\Support\Collection;

/**
 * Lock the cart's products for the rest of the transaction, so the stock read
 * the next pipe makes cannot be overtaken by another order committing between
 * the two.
 */
class LockProducts
{
    public function handle(
        OrderProcessingContext $context,
        Closure $next,
    ): OrderProcessingContext {
        $productIds = $context
            ->cartOrFail()
            ->lines
            ->map(static fn (CartLineData $line): ?int => $line->product?->getKey())
            ->filter()
            ->all();

        /** @var Collection<int, Product> $products */
        $products = Product::query()
            ->whereKey($productIds)
            ->lockForUpdate()
            ->get()
            ->keyBy(static fn (Product $product): int => (int) $product->getKey());

        return $next(
            $context->withLockedProducts($products)->recordPipe(self::class),
        );
    }
}
