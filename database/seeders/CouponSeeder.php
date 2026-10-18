<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DiscountType;
use App\Models\Coupon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CouponSeeder extends Seeder
{
    /**
     * Launch coupons. `value` is a percentage for percentage coupons and a BHD
     * amount for fixed ones; `max_discount` and `min_order` are BHD amounts. The
     * model stores every amount in fils.
     *
     * @var array<int, array{code: string, description: string, type: DiscountType, value: float, max_discount: float, min_order: int, usage_limit: int, expires_in: int}>
     */
    private const COUPONS = [
        [
            'code' => 'WELCOME10',
            'description' => '10% off your first order',
            'type' => DiscountType::PERCENTAGE,
            'value' => 10.0,
            'max_discount' => 10.0,
            'min_order' => 50,
            'usage_limit' => 1000,
            'expires_in' => 180,
        ],
        [
            'code' => 'SAVE5',
            'description' => 'BHD 5 off orders over BHD 75',
            'type' => DiscountType::FIXED_AMOUNT,
            'value' => 5.0,
            'max_discount' => 5.0,
            'min_order' => 75,
            'usage_limit' => 500,
            'expires_in' => 90,
        ],
        [
            'code' => 'FREESHIP',
            'description' => 'Free delivery on orders over BHD 20',
            'type' => DiscountType::FIXED_AMOUNT,
            'value' => 2.0,
            'max_discount' => 2.0,
            'min_order' => 20,
            'usage_limit' => 2000,
            'expires_in' => 365,
        ],
        [
            'code' => 'SUMMER15',
            'description' => '15% off for summer, up to BHD 30',
            'type' => DiscountType::PERCENTAGE,
            'value' => 15.0,
            'max_discount' => 30.0,
            'min_order' => 100,
            'usage_limit' => 300,
            'expires_in' => 60,
        ],
        [
            'code' => 'GAMER20',
            'description' => '20% off orders over BHD 200, up to BHD 50',
            'type' => DiscountType::PERCENTAGE,
            'value' => 20.0,
            'max_discount' => 50.0,
            'min_order' => 200,
            'usage_limit' => 100,
            'expires_in' => 45,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::COUPONS as $coupon) {
            Coupon::updateOrCreate(
                ['code' => Str::lower($coupon['code'])],
                [
                    'description' => $coupon['description'],
                    'discount_type' => $coupon['type']->value,
                    'discount_value' => $coupon['value'],
                    'maximum_discount' => $coupon['max_discount'],
                    'min_order_amount' => $coupon['min_order'],
                    'usage_limit' => $coupon['usage_limit'],
                    'used_count' => 0,
                    'activated_at' => now()->subWeek(),
                    'starts_at' => now()->subWeek(),
                    'expires_at' => now()->addDays($coupon['expires_in']),
                ],
            );
        }
    }
}
