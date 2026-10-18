<?php

declare(strict_types=1);

namespace App\Pipelines\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Pipelines\Orders\Pipes\NotifyLowStock;
use App\Pipelines\Orders\Pipes\RecalculateReservedStock;
use App\Pipelines\Orders\Pipes\UpdateOrderStatus;

/**
 * Taking a called-off order up again holds its lines once more, which can
 * leave a product short.
 */
final class OrderReinstater extends OrderPipeline
{
    protected const array PIPES = [
        UpdateOrderStatus::class,
        RecalculateReservedStock::class,
        NotifyLowStock::class,
    ];

    public function __invoke(Order $order): Order
    {
        return $this->changeStatus($order, OrderStatus::PENDING);
    }
}
