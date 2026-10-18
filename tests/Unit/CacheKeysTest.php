<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\CacheKeys;
use Tests\TestCase;

class CacheKeysTest extends TestCase
{
    public function test_a_key_without_parts_is_just_its_value(): void
    {
        static::assertSame(
            'dashboard_revenue',
            CacheKeys::DashboardRevenue->key(),
        );
    }

    public function test_parts_are_appended_so_a_figure_can_vary_by_period(): void
    {
        static::assertSame(
            'dashboard_revenue:thirty_days',
            CacheKeys::DashboardRevenue->key('thirty_days'),
        );

        static::assertSame(
            'dashboard_revenue_trend:7',
            CacheKeys::DashboardRevenueTrend->key(7),
        );
    }

    public function test_parts_are_joined_in_order(): void
    {
        static::assertSame(
            'applicable_product_discounts:a:b:c',
            CacheKeys::ApplicableProductDiscounts->key('a', 'b', 'c'),
        );
    }
}
