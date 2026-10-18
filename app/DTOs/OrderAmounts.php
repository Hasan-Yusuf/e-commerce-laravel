<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Support\Money;

final readonly class OrderAmounts
{
    public function __construct(
        public Money $subtotal,
        public Money $tax,
        public Money $shipping,
        public Money $discount,
        public Money $total,
    ) {}

    /**
     * The order columns the amounts are written to. Each holds a Money, which
     * the `*_in_fils` columns take as their own through App\Casts\AsMoney.
     *
     * @return array{subtotal_in_fils: Money, tax_amount_in_fils: Money, shipping_amount_in_fils: Money, discount_amount_in_fils: Money, total_amount_in_fils: Money}
     */
    public function toArray(): array
    {
        return [
            'subtotal_in_fils' => $this->subtotal,
            'tax_amount_in_fils' => $this->tax,
            'shipping_amount_in_fils' => $this->shipping,
            'discount_amount_in_fils' => $this->discount,
            'total_amount_in_fils' => $this->total,
        ];
    }
}
