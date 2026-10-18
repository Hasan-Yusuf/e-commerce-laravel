<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Reference data first (brands, categories, types, products, promotions),
     * then people (users, addresses) and finally their orders.
     */
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            BrandSeeder::class,
            CategorySeeder::class,
            TypeSeeder::class,
            ProductSeeder::class,
            DiscountSeeder::class,
            BannerSeeder::class,
            CouponSeeder::class,

            UserSeeder::class,
            AddressSeeder::class,
            OrderSeeder::class,
            PaymentSeeder::class,
        ]);
    }
}
