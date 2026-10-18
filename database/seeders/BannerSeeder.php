<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Banner;
use Database\Seeders\Concerns\SeedsSampleImages;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    use SeedsSampleImages;

    /**
     * How many slides the hero is seeded with.
     */
    private const int COUNT = 5;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $illustrated = $this->shouldSeedSampleImage();

        if ($illustrated) {
            Banner::factory()->withImage()->create();
        }

        Banner::factory()
            ->count(self::COUNT - (int) $illustrated)
            ->create();
    }
}
