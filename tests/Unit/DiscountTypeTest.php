<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\DiscountType;
use Tests\TestCase;

/**
 * A discount's value, read in the unit its type gives it.
 */
class DiscountTypeTest extends TestCase
{
    public function test_a_share_reads_with_the_percent_sign(): void
    {
        static::assertSame('12.50%', DiscountType::PERCENTAGE->formatValue(12.5));
    }

    public function test_an_amount_reads_as_money_the_way_the_panel_writes_it(): void
    {
        // ICU separates the amount from the currency code with a non-breaking
        // space, the same one every other BHD column in the panel carries.
        static::assertSame("BHD\u{00A0}12.500", DiscountType::FIXED_AMOUNT->formatValue(12.5));
        static::assertSame("BHD\u{00A0}1,000.000", DiscountType::FIXED_AMOUNT->formatValue(1_000));
    }
}
