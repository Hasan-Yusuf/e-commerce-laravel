<?php

declare(strict_types=1);

namespace App\Pipelines\Orders;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Pipelines\Orders\Pipes\AdjustOnHandStock;
use App\Pipelines\Orders\Pipes\NotifyLowStock;
use App\Pipelines\Orders\Pipes\RecalculateReservedStock;
use App\Pipelines\Orders\Pipes\UpdateOrderStatus;

final class OrderFulfiller extends OrderPipeline
{
    protected const array PIPES = [
        UpdateOrderStatus::class,
        RecalculateReservedStock::class,
        AdjustOnHandStock::class,
        NotifyLowStock::class,
    ];

    /**
     * The status is the shopper's own decision, already recorded on the order,
     * so fulfilling asks the admin for nothing; a caller may still name a
     * status outright in `$data`.
     *
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Order $order, array $data = []): Order
    {
        return $this->changeStatus($order, $this->target($order, $data), $data);
    }

    /**
     * Where the order came from is where it is fulfilled: an order to be brought
     * leaves the shop, one to be collected is handed over in it.
     */
    public static function fulfilmentStatusFor(Order $order): OrderStatus
    {
        return $order->delivery_method->is(DeliveryMethod::PICKUP)
            ? OrderStatus::DELIVERED
            : OrderStatus::SHIPPED;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function target(Order $order, array $data): OrderStatus
    {
        $status = $data['status'] ?? null;

        if ($status instanceof OrderStatus) {
            return $status;
        }

        return OrderStatus::tryFrom((string) $status) ?? self::fulfilmentStatusFor($order);
    }
}
