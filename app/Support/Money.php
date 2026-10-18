<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Number;
use Stringable;

/**
 * Money as the storefront writes it: the amount, then the currency beside it.
 *
 * The amount is kept in fils wherever it is stored, so the conversion to what a
 * shopper reads lives here rather than in every view that shows a price — one
 * place decides how money reads, and every price agrees with every other. The
 * arithmetic is integer-only for the same reason: a sum of whole fils never
 * drifts the way a sum of binary floats does.
 *
 * A model's `*_in_fils` column reached through App\Casts\AsMoney is one of
 * these, which is what lets a total be written as `$order->subtotal_in_fils`
 * added to the tax and shipping and read back out with a currency.
 */
final class Money implements Stringable
{
    /**
     * The number of fils in one currency unit (1 BHD = 1000 fils).
     */
    public const int FILS_PER_UNIT = 1000;

    public function __construct(
        private int $fils,
    ) {}

    /**
     * An amount held in fils — the integer the orders, the cart and the coupons
     * all count in.
     */
    public static function fromFils(int $fils): self
    {
        return new self($fils);
    }

    /**
     * An amount already in the major unit, such as a filter's price bound or a
     * value read from a form.
     */
    public static function fromBHD(int|float|string $bhd): self
    {
        return new self((int) round((float) $bhd * self::FILS_PER_UNIT));
    }

    /**
     * An amount in the major unit; an alias for fromBHD().
     */
    public static function amount(int|float|string $amount): self
    {
        return self::fromBHD($amount);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function toFils(): int
    {
        return $this->fils;
    }

    /**
     * The amount in the major unit: for a numeric field, an export column or an
     * amount handed to a helper that expects BHD.
     */
    public function toAmount(): float
    {
        return round($this->fils / self::FILS_PER_UNIT, 3, PHP_ROUND_HALF_UP);
    }

    public function add(self $other): self
    {
        return new self($this->fils + $other->fils);
    }

    public function subtract(self $other): self
    {
        return new self($this->fils - $other->fils);
    }

    /**
     * The amount repeated, as a line total repeats its unit price.
     */
    public function multiply(int $quantity): self
    {
        return new self($this->fils * $quantity);
    }

    public function isZero(): bool
    {
        return $this->fils === 0;
    }

    public function isPositive(): bool
    {
        return $this->fils > 0;
    }

    public function isNegative(): bool
    {
        return $this->fils < 0;
    }

    public function equalsFils(int $fils): bool
    {
        return $this->fils === $fils;
    }

    public function equals(self $other): bool
    {
        return $this->fils === $other->fils;
    }

    public function equalsBHD(float|int $bhd): bool
    {
        return $this->equalsFils(self::fromBHD($bhd)->toFils());
    }

    public function greaterThanFils(int $fils): bool
    {
        return $this->fils > $fils;
    }

    public function greaterThan(self $other): bool
    {
        return $this->fils > $other->fils;
    }

    public function lessThanFils(int $fils): bool
    {
        return $this->fils < $fils;
    }

    public function lessThan(self $other): bool
    {
        return $this->fils < $other->fils;
    }

    public function greaterThanBHD(float|int $bhd): bool
    {
        return $this->greaterThanFils(self::fromBHD($bhd)->toFils());
    }

    /**
     * Many amounts added together, in whole fils.
     *
     * @param  iterable<int, self>  $amounts
     */
    public static function sum(iterable $amounts): self
    {
        $fils = 0;

        foreach ($amounts as $amount) {
            $fils += $amount->fils;
        }

        return new self($fils);
    }

    /**
     * A percentage of a fils amount, rounded half up. The rate is a plain
     * number (50 for 50%), so it is held in basis points for the multiplication
     * — the one place a percentage of money is worked out, shared by tax,
     * discounts and coupons so they all round the same way.
     */
    public static function percentage(int $fils, int|float|string $percentage): int
    {
        $basisPoints = (int) round((float) $percentage * 100);

        return intdiv(($fils * $basisPoints) + 5_000, 10_000);
    }

    /**
     * The amount as a shopper reads it: the number, then the currency.
     */
    public function toBHD(): string
    {
        return Number::format($this->toAmount(), maxPrecision: 3).' '.__('homepage.currency');
    }

    public function __toString(): string
    {
        return $this->toBHD();
    }
}
