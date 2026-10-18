<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Support\Collection;

final readonly class CartSummary
{
    /**
     * @param  Collection<int, CartLineData>  $lines
     * @param  int  $discountInFils  the order-level coupon discount, on top of the
     *                               product discounts already baked into each line
     */
    public function __construct(
        public Collection $lines,
        public int $itemCount,
        public int $subtotalInFils,
        public int $discountInFils = 0,
        public ?AppliedCoupon $coupon = null,
    ) {}

    public function totalInFils(): int
    {
        return $this->subtotalInFils - $this->discountInFils;
    }

    public function isEmpty(): bool
    {
        return $this->lines->isEmpty();
    }

    /**
     * A copy with the given coupon applied, its discount folded into the total.
     */
    public function withCoupon(?AppliedCoupon $coupon): self
    {
        return new self(
            lines: $this->lines,
            itemCount: $this->itemCount,
            subtotalInFils: $this->subtotalInFils,
            discountInFils: $coupon === null ? 0 : $coupon->discountInFils,
            coupon: $coupon,
        );
    }
}
