<?php

declare(strict_types=1);

namespace App\Pipelines\Orders\Pipes;

use App\DTOs\OrderProcessingContext;
use App\Enums\OrderStatus;
use Closure;

/**
 * Its own pipe so that everything after it reads the order the way the rest of
 * the application now will — in particular, the status a hold is derived
 * against. A delivered order also records when it was delivered.
 */
class UpdateOrderStatus
{
    public function handle(
        OrderProcessingContext $context,
        Closure $next,
    ): OrderProcessingContext {
        $order = $context->orderOrFail();
        $status = $context->targetStatusOrFail();

        $attributes = ['status' => $status];

        if (OrderStatus::DELIVERED->is($status)) {
            $attributes['delivered_at'] = $context->data['delivered_at'] ?? $order->delivered_at ?? now();
        }

        $order->update($attributes);

        return $next($context->recordPipe(self::class));
    }
}
