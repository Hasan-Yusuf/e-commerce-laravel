<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Order;
use App\Services\Order\OrderNumberService;

/**
 * Keeps an order's number and total in step with its own columns. The number
 * comes from the sequence service so two concurrent placements cannot read the
 * newest order and take the same number; the total is recomputed from the
 * order's integer-fils parts whenever one of them changes.
 */
class OrderObserver
{
    public function __construct(
        private OrderNumberService $orderNumberService,
    ) {}

    public function creating(Order $order): void
    {
        $order->number = $this->orderNumberService->next();

        $this->calculateTotal($order);
    }

    public function updating(Order $order): void
    {
        $this->calculateTotal($order);
    }

    private function calculateTotal(Order $order): void
    {
        $order->total_amount_in_fils = $order
            ->subtotal_in_fils
            ->add($order->tax_amount_in_fils)
            ->add($order->shipping_amount_in_fils)
            ->subtract($order->discount_amount_in_fils);
    }
}
