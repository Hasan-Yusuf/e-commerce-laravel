<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Enums\DiscountType;
use App\Support\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * The coupon's money as the admin enters it: BHD amounts for the fixed value,
 * the cap and the minimum order, and the percentage itself for a share.
 *
 * Each column holds one unit rather than one column holding either — a share in
 * `discount_percentage`, money in integer fils (`_in_fils`) — so nothing has to
 * read the column's type to know what a value means. These accessors are the
 * one place a value is written from BHD or read back into it.
 *
 * @property DiscountType $discount_type
 */
trait HasCouponAmounts
{
    public function discountValue(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?float => $this->discount_type->is(DiscountType::PERCENTAGE)
                ? self::toPercentageOrNull($attributes['discount_percentage'] ?? null)
                : self::toAmountOrNull($attributes['discount_value_in_fils'] ?? null),
            set: fn (mixed $value): array => (
                $this->discount_type->is(DiscountType::PERCENTAGE)
                    ? [
                        'discount_percentage' => (float) $value,
                        'discount_value_in_fils' => null,
                    ]
                    : [
                        'discount_value_in_fils' => Money::fromBHD((float) $value)->toFils(),
                        'discount_percentage' => null,
                    ]
            ),
        );
    }

    /**
     * The most the coupon may take off, in BHD, or null when it is uncapped.
     */
    public function maximumDiscount(): Attribute
    {
        return Attribute::make(
            get: static fn (mixed $value, array $attributes): ?float => self::toAmountOrNull(
                $attributes['maximum_discount_in_fils'] ?? null,
            ),
            set: static fn (mixed $value): array => [
                'maximum_discount_in_fils' => $value === null || $value === ''
                        ? null
                        : Money::fromBHD((float) $value)->toFils(),
            ],
        );
    }

    /**
     * The subtotal the coupon needs before it applies, in BHD.
     */
    public function minOrderAmount(): Attribute
    {
        return Attribute::make(
            get: static fn (mixed $value): float => Money::fromFils((int) round((float) $value))->toAmount(),
            set: static fn (mixed $value): array => [
                'min_order_amount' => $value === null || $value === ''
                        ? 0
                        : Money::fromBHD((float) $value)->toFils(),
            ],
        );
    }

    /**
     * A stored fils amount as BHD, or null when there is none.
     */
    private static function toAmountOrNull(mixed $fils): ?float
    {
        return $fils === null
            ? null
            : Money::fromFils((int) round((float) $fils))->toAmount();
    }

    /**
     * A stored percentage as a float, or null when there is none. A share is not
     * money, so it is not passed through the fils conversion.
     */
    private static function toPercentageOrNull(mixed $percentage): ?float
    {
        return $percentage === null ? null : (float) $percentage;
    }
}
