<?php

declare(strict_types=1);

namespace App\Pipelines\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Pipelines\Orders\Pipes\RecalculateReservedStock;
use App\Pipelines\Orders\Pipes\UpdateOrderStatus;

/**
 * Nothing about the shelf changes when an order is confirmed — the lines stay
 * open — but the hold is re-derived all the same, so the figure cannot drift.
 */
final class OrderConfirmer extends OrderPipeline
{
    protected const array PIPES = [
        UpdateOrderStatus::class,
        RecalculateReservedStock::class,
    ];

    public function __invoke(Order $order): Order
    {
        return $this->changeStatus($order, OrderStatus::PROCESSING);
    }
}
