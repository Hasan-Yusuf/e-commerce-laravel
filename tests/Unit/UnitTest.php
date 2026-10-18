<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Unit;
use Tests\TestCase;

/**
 * The unit a number is entered in, and the side each locale writes it on.
 */
class UnitTest extends TestCase
{
    public function test_a_left_to_right_locale_writes_the_unit_after_the_number(): void
    {
        $this->app->setLocale('en');

        static::assertSame('BHD', Unit::CURRENCY->suffix());
        static::assertNull(Unit::CURRENCY->prefix());

        static::assertSame('KG', Unit::WEIGHT->suffix());
        static::assertNull(Unit::WEIGHT->prefix());
    }

    public function test_a_right_to_left_locale_writes_the_unit_before_the_number(): void
    {
        $this->app->setLocale('ar');

        static::assertSame('د.ب', Unit::CURRENCY->prefix());
        static::assertNull(Unit::CURRENCY->suffix());

        static::assertSame('كجم', Unit::WEIGHT->prefix());
        static::assertNull(Unit::WEIGHT->suffix());
    }

    public function test_a_percentage_reads_the_same_in_both_locales(): void
    {
        $this->app->setLocale('en');
        static::assertSame('%', Unit::PERCENT->suffix());

        $this->app->setLocale('ar');
        static::assertSame('%', Unit::PERCENT->prefix());
    }

    public function test_a_number_is_written_with_the_unit_at_the_units_own_precision(): void
    {
        $this->app->setLocale('en');

        // Money is written to the fil and a measured number to two places.
        static::assertSame('12.500BHD', Unit::CURRENCY->format(12.5));
        static::assertSame('1.50KG', Unit::WEIGHT->format(1.5));
        static::assertSame('10.00%', Unit::PERCENT->format(10));
    }

    public function test_a_number_leads_with_the_unit_where_the_locale_reads_it_that_way(): void
    {
        $this->app->setLocale('ar');

        static::assertSame('د.ب12.500', Unit::CURRENCY->format(12.5));
    }
}
