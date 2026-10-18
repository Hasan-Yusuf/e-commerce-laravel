<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DiscountType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Type;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    /**
     * Realistic promotional campaigns, attached to the categories, types,
     * brands or products they target.
     *
     * @var array<int, array{name: string, type: DiscountType, value: float, days: int, categories?: array<int, string>, types?: array<int, string>, brands?: array<int, string>, products?: array<int, string>}>
     */
    private const CAMPAIGNS = [
        [
            'name' => 'Summer Sale',
            'type' => DiscountType::PERCENTAGE,
            'value' => 20.0,
            'days' => 45,
            'categories' => ['electronics', 'fashion'],
        ],
        [
            'name' => 'Home Refresh',
            'type' => DiscountType::PERCENTAGE,
            'value' => 15.0,
            'days' => 30,
            'categories' => ['home-kitchen'],
        ],
        [
            'name' => 'Smartphone Week',
            'type' => DiscountType::PERCENTAGE,
            'value' => 10.0,
            'days' => 14,
            'types' => ['Smartphones'],
        ],
        [
            'name' => 'Outdoor Season',
            'type' => DiscountType::PERCENTAGE,
            'value' => 12.0,
            'days' => 40,
            'categories' => ['sports-outdoors'],
        ],
        [
            'name' => 'Brand Week: Nova & Atlas',
            'type' => DiscountType::PERCENTAGE,
            'value' => 10.0,
            'days' => 14,
            'brands' => ['Nova', 'Atlas'],
        ],
        [
            'name' => 'Skincare Favourites',
            'type' => DiscountType::PERCENTAGE,
            'value' => 8.0,
            'days' => 10,
            'categories' => ['skincare'],
        ],
        [
            'name' => 'Clearance: Last Season Picks',
            'type' => DiscountType::FIXED_AMOUNT,
            'value' => 10.0,
            'days' => 20,
            'products' => ['Solstice S10 Lite', 'Lumina Watch S'],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::CAMPAIGNS as $campaign) {
            $discount = Discount::updateOrCreate(
                ['name' => $campaign['name']],
                [
                    'type' => $campaign['type'],
                    'value' => $campaign['value'],
                    'starts_at' => now()->subMonth(),
                    'ends_at' => now()->addDays($campaign['days']),
                    'activated_at' => now()->subMonth(),
                ],
            );

            $discount
                ->categories()
                ->sync(
                    Category::query()
                        ->whereIn(
                            'slug',
                            $campaign['categories'] ?? [],
                        )
                        ->pluck('id'),
                );
            $discount
                ->productTypes()
                ->sync(
                    Type::query()
                        ->whereIn('name', $campaign['types'] ?? [])
                        ->pluck(
                            'id',
                        ),
                );
            $discount
                ->brands()
                ->sync(
                    Brand::query()
                        ->whereIn('name_en', $campaign['brands'] ?? [])
                        ->pluck(
                            'id',
                        ),
                );
            $discount
                ->products()
                ->sync(
                    Product::query()
                        ->whereIn(
                            'name',
                            $campaign['products'] ?? [],
                        )
                        ->pluck('id'),
                );
        }
    }
}
