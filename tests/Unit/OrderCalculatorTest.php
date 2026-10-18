<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Order\OrderCalculator;
use App\Support\Money;
use Tests\TestCase;

class OrderCalculatorTest extends TestCase
{
    public function test_sums_line_totals_in_whole_fils_without_float_drift(): void
    {
        $lines = [
            ['unit_price' => 0.1, 'quantity' => 1],
            ['unit_price' => 0.1, 'quantity' => 1],
            ['unit_price' => 0.1, 'quantity' => 1],
        ];

        // The same sum in floats is 0.30000000000000004.
        static::assertSame(300, OrderCalculator::subtotal($lines)->toFils());
        static::assertSame(0.3, OrderCalculator::subtotal($lines)->toAmount());
    }

    public function test_multiplies_the_unit_price_by_the_quantity(): void
    {
        static::assertSame(
            35_000,
            OrderCalculator::lineTotal(Money::fromFils(17_500), 2)->toFils(),
        );
    }

    public function test_ignores_lines_that_are_missing_their_values(): void
    {
        $lines = [
            ['unit_price' => 10, 'quantity' => 2],
            ['unit_price' => 10],
            ['quantity' => 3],
            [],
        ];

        static::assertSame(20_000, OrderCalculator::subtotal($lines)->toFils());
    }

    public function test_calculates_tax_from_a_percentage(): void
    {
        static::assertSame(
            1_500,
            OrderCalculator::tax(Money::fromFils(10_000), 15)->toFils(),
        );

        static::assertSame(
            750,
            OrderCalculator::tax(Money::fromFils(10_000), 7.5)->toFils(),
        );

        static::assertSame(0, OrderCalculator::tax(Money::fromFils(10_000), 0)->toFils());
        static::assertSame(0, OrderCalculator::tax(Money::fromFils(10_000), null)->toFils());
    }

    public function test_rounds_the_tax_to_the_nearest_fil(): void
    {
        // 50% of 3 fils is 1.5 fils, rounded half up to 2.
        static::assertSame(2, OrderCalculator::tax(Money::fromFils(3), 50)->toFils());

        // 50% of 1 fil is 0.5 fils, rounded half up to 1.
        static::assertSame(1, OrderCalculator::tax(Money::fromFils(1), 50)->toFils());
    }

    public function test_calculates_the_total(): void
    {
        static::assertSame(
            23_500,
            OrderCalculator::total(
                Money::fromFils(20_000),
                Money::fromFils(3_000),
                Money::fromFils(1_000),
                Money::fromFils(500),
            )->toFils(),
        );
    }
}
