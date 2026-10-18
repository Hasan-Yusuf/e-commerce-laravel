<?php

declare(strict_types=1);

namespace App\Pipelines\Orders\Pipes;

use App\DTOs\OrderProcessingContext;
use App\Enums\OrderStatus;
use App\Models\Product;
use Closure;

/**
 * The only pipe that writes `stock`, and only the transitions that move goods
 * carry it. The direction comes from the status the order is being moved to,
 * so the two chains that carry it cannot run it the wrong way round.
 */
class AdjustOnHandStock
{
    public function handle(
        OrderProcessingContext $context,
        Closure $next,
    ): OrderProcessingContext {
        $restocking = match ($context->targetStatusOrFail()) {
            OrderStatus::SHIPPED, OrderStatus::DELIVERED => false,
            OrderStatus::REFUNDED => true,
            default => null,
        };

        if ($restocking === null) {
            return $next($context->recordPipe(self::class));
        }

        foreach ($context->orderOrFail()->orderItems as $item) {
            $productId = $item->product_id;

            if ($productId === null) {
                continue;
            }

            $delta = $restocking
                ? (int) $item->quantity
                : -(int) $item->quantity;

            Product::query()
                ->whereKey($productId)
                ->increment('stock', $delta);
        }

        return $next($context->recordPipe(self::class));
    }
}
