<?php

declare(strict_types=1);

namespace App\Pipelines\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Pipelines\Orders\Pipes\AdjustOnHandStock;
use App\Pipelines\Orders\Pipes\RecalculateReservedStock;
use App\Pipelines\Orders\Pipes\UpdateOrderStatus;

/**
 * A refund only ever loosens the stock, so nothing is reported: there is no
 * shortage a refund could cause.
 */
final class OrderRefunder extends OrderPipeline
{
    protected const array PIPES = [
        UpdateOrderStatus::class,
        RecalculateReservedStock::class,
        AdjustOnHandStock::class,
    ];

    public function __invoke(Order $order): Order
    {
        return $this->changeStatus($order, OrderStatus::REFUNDED);
    }
}
