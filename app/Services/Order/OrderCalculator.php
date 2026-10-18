<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\DTOs\AppliedCoupon;
use App\DTOs\CartLineData;
use App\DTOs\CartSummary;
use App\DTOs\OrderAmounts;
use App\Support\Money;

/**
 * The order's money arithmetic, in whole fils.
 *
 * Every amount is a Money, so no binary floating point drift can accumulate
 * across a calculation and the conversion to and from a decimal amount lives in
 * Money alone — nothing here counts fils itself.
 */
class OrderCalculator
{
    /**
     * The amounts a cart is placed from, with an applied coupon folded in. The
     * cart's own coupon is read when none is passed, so a cart that already
     * carries one prices the same as one handed it here.
     *
     * The tax rate and the flat shipping charge are the merchant's own figures,
     * entered in the admin panel, so they arrive as arguments rather than
     * constants. A value left blank prices as zero.
     */
    public static function forCart(
        CartSummary $cart,
        ?AppliedCoupon $coupon = null,
        int|float|string|null $taxPercentage = null,
        int|float|string|Money|null $shippingInFils = null,
    ): OrderAmounts {
        $subtotal = Money::sum(
            $cart->lines->map(
                static fn (CartLineData $line): Money => Money::fromFils(
                    $line->lineTotalInFils(),
                ),
            ),
        );

        $tax = self::tax($subtotal, $taxPercentage);

        $shipping = $shippingInFils instanceof Money
            ? $shippingInFils
            : Money::fromFils((int) ($shippingInFils ?? 0));

        $discount = Money::fromFils(
            $coupon === null ? $cart->discountInFils : $coupon->discountInFils,
        );

        $total = self::total($subtotal, $tax, $shipping, $discount);

        return new OrderAmounts(
            subtotal: $subtotal,
            tax: $tax,
            shipping: $shipping,
            discount: $discount,
            total: $total,
        );
    }

    /**
     * A line's total: the unit price multiplied by the quantity.
     */
    public static function lineTotal(
        int|float|string|Money|null $unitPrice,
        int $quantity,
    ): Money {
        return self::money($unitPrice)->multiply($quantity);
    }

    /**
     * The sum of every line's total.
     *
     * @param  iterable<int|string, array{unit_price?: int|float|string|Money|null, quantity?: int|string|null}>  $lines
     */
    public static function subtotal(iterable $lines): Money
    {
        $totals = [];

        foreach ($lines as $line) {
            $totals[] = self::lineTotal(
                $line['unit_price'] ?? 0,
                (int) ($line['quantity'] ?? 0),
            );
        }

        return Money::sum($totals);
    }

    /**
     * The tax for a subtotal. The rate is a percentage, applied by
     * Money::percentage() so it rounds the same way discounts and coupons do.
     */
    public static function tax(
        Money $subtotal,
        int|float|string|null $taxPercentage,
    ): Money {
        return Money::fromFils(
            Money::percentage($subtotal->toFils(), $taxPercentage ?? 0),
        );
    }

    /**
     * The order total: subtotal + tax + shipping - discount.
     */
    public static function total(
        Money $subtotal,
        Money $tax,
        Money $shipping,
        Money $discount,
    ): Money {
        return $subtotal->add($tax)->add($shipping)->subtract($discount);
    }

    /**
     * An amount a caller holds as a Money, a decimal amount or nothing.
     */
    private static function money(int|float|string|Money|null $amount): Money
    {
        return $amount instanceof Money ? $amount : Money::fromBHD($amount ?? 0);
    }
}
