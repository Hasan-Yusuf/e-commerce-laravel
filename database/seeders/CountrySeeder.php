<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Countries the store ships to, keyed for phone-number validation.
     *
     * @var array<int, array{name_en: string, name_ar: string, slug: string, calling_code: string, phone_length: int}>
     */
    private const COUNTRIES = [
        [
            'name_en' => 'Bahrain',
            'name_ar' => 'البحرين',
            'slug' => 'bahrain',
            'calling_code' => '973',
            'phone_length' => 8,
        ],
        [
            'name_en' => 'Saudi Arabia',
            'name_ar' => 'المملكة العربية السعودية',
            'slug' => 'saudi-arabia',
            'calling_code' => '966',
            'phone_length' => 9,
        ],
        [
            'name_en' => 'United Arab Emirates',
            'name_ar' => 'الإمارات العربية المتحدة',
            'slug' => 'united-arab-emirates',
            'calling_code' => '971',
            'phone_length' => 9,
        ],
        [
            'name_en' => 'Kuwait',
            'name_ar' => 'الكويت',
            'slug' => 'kuwait',
            'calling_code' => '965',
            'phone_length' => 8,
        ],
        [
            'name_en' => 'Qatar',
            'name_ar' => 'قطر',
            'slug' => 'qatar',
            'calling_code' => '974',
            'phone_length' => 8,
        ],
        [
            'name_en' => 'Oman',
            'name_ar' => 'عُمان',
            'slug' => 'oman',
            'calling_code' => '968',
            'phone_length' => 8,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::COUNTRIES as $country) {
            Country::updateOrCreate(
                ['slug' => $country['slug']],
                [
                    'name_en' => $country['name_en'],
                    'name_ar' => $country['name_ar'],
                    'calling_code' => $country['calling_code'],
                    'phone_length' => $country['phone_length'],
                ],
            );
        }
    }
}
