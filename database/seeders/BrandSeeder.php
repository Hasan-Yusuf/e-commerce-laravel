<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Fictional storefront brands, with their Arabic names.
     *
     * @var array<int, array{en: string, ar: string}>
     */
    private const BRANDS = [
        ['en' => 'Northwind', 'ar' => 'نورثويند'],
        ['en' => 'Auralis', 'ar' => 'أوراليس'],
        ['en' => 'Verdant', 'ar' => 'فيردانت'],
        ['en' => 'Solstice', 'ar' => 'سولستيس'],
        ['en' => 'Meridian', 'ar' => 'ميريديان'],
        ['en' => 'Lumina', 'ar' => 'لومينا'],
        ['en' => 'Atlas', 'ar' => 'أطلس'],
        ['en' => 'Terra', 'ar' => 'تيرا'],
        ['en' => 'Cascade', 'ar' => 'كاسكيد'],
        ['en' => 'Ember', 'ar' => 'إمبر'],
        ['en' => 'Harbor', 'ar' => 'هاربر'],
        ['en' => 'Nova', 'ar' => 'نوفا'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::BRANDS as $brand) {
            Brand::updateOrCreate(
                ['name_en' => $brand['en']],
                ['name_ar' => $brand['ar']],
            );
        }
    }
}
