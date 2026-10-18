<?php

declare(strict_types=1);

namespace App\Pipelines\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Pipelines\Orders\Pipes\RecalculateReservedStock;
use App\Pipelines\Orders\Pipes\UpdateOrderStatus;

/**
 * Nothing leaves the shop when an order is called off, so the on-hand stock
 * does not move — only the hold the lines were keeping is released.
 */
final class OrderCanceller extends OrderPipeline
{
    protected const array PIPES = [
        UpdateOrderStatus::class,
        RecalculateReservedStock::class,
    ];

    public function __invoke(Order $order): Order
    {
        return $this->changeStatus($order, OrderStatus::CANCELLED);
    }
}
