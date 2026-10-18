<?php

declare(strict_types=1);

namespace App\Pipelines\Orders;

use App\DTOs\OrderProcessingContext;
use App\Models\Order;
use App\Pipelines\Orders\Pipes\NotifyLowStock;
use App\Pipelines\Orders\Pipes\RecalculateReservedStock;

/**
 * The panel's order form writes an order and its lines directly, so the hold
 * they imply has to be derived afterwards — the same recalculation a placed
 * order gets, which is why it can be run again without changing anything. No
 * status is written: this is not a transition.
 */
final class OrderReserver extends OrderPipeline
{
    protected const array PIPES = [
        RecalculateReservedStock::class,
        NotifyLowStock::class,
    ];

    public function __invoke(Order $order): Order
    {
        return $this->run(
            new OrderProcessingContext(order: $order->loadMissing('orderItems')),
        )->orderOrFail();
    }
}
