<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Money;
use Tests\TestCase;

/**
 * Money as the storefront writes it: the amount, then the currency beside it.
 */
class MoneyTest extends TestCase
{
    public function test_an_amount_held_in_fils_is_written_with_the_currency_beside_it(): void
    {
        static::assertSame('10 BHD', Money::fromFils(10_000)->toBHD());
        static::assertSame('7.5 BHD', Money::fromFils(7_500)->toBHD());
        static::assertSame('12.25 BHD', Money::fromFils(12_250)->toBHD());
    }

    public function test_an_amount_already_in_the_major_unit_is_written_the_same_way(): void
    {
        static::assertSame('1.5 BHD', Money::amount(1.5)->toBHD());
        static::assertSame('2 BHD', Money::amount(2)->toBHD());
    }

    public function test_money_reads_back_as_the_fils_and_the_major_unit_it_holds(): void
    {
        $money = Money::fromBHD(12.345);

        static::assertSame(12_345, $money->toFils());
        static::assertSame(12.345, $money->toAmount());
    }

    public function test_amounts_add_subtract_and_multiply_in_whole_fils(): void
    {
        $subtotal = Money::fromFils(25_000);

        static::assertSame(
            52_000,
            $subtotal
                ->add(Money::fromFils(2_500))
                ->subtract(Money::fromFils(1_500))
                ->multiply(2)
                ->toFils(),
        );

        static::assertSame(
            15_000,
            Money::sum([
                Money::fromFils(10_000),
                Money::fromFils(3_000),
                Money::fromFils(2_000),
            ])->toFils(),
        );
    }

    public function test_money_reports_where_it_stands_against_another_amount(): void
    {
        static::assertTrue(Money::fromFils(1)->isPositive());
        static::assertTrue(Money::zero()->isZero());
        static::assertTrue(Money::fromFils(-1)->isNegative());
        static::assertTrue(Money::fromFils(10)->greaterThan(Money::fromFils(5)));
        static::assertTrue(Money::fromFils(5)->equals(Money::fromFils(5)));
    }

    public function test_the_currency_follows_the_number_in_the_locale_it_is_written_in(): void
    {
        $this->app->setLocale('ar');

        static::assertSame('10 د.ب', Money::fromFils(10_000)->toBHD());
    }

    public function test_a_percentage_of_money_is_a_share_in_whole_fils(): void
    {
        static::assertSame(500, Money::percentage(1_000, 50));
        static::assertSame(0, Money::percentage(1_000, 0));
    }

    public function test_a_fractional_percentage_is_kept_to_its_basis_points(): void
    {
        static::assertSame(333, Money::percentage(1_000, 33.33));
        static::assertSame(125, Money::percentage(1_000, 12.5));
    }

    public function test_a_fraction_of_a_fil_rounds_half_up(): void
    {
        // Half a fil rounds up to one; just under half rounds down to none.
        static::assertSame(1, Money::percentage(1, 50));
        static::assertSame(0, Money::percentage(1, 49));
    }

    public function test_a_rate_may_be_written_as_a_string_or_a_float(): void
    {
        static::assertSame(500, Money::percentage(1_000, '50'));
        static::assertSame(1_250, Money::percentage(10_000, 12.5));
    }
}
