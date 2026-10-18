<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'title_en' => fake()->sentence(3),

            'title_ar' => fake('ar')->sentence(3),

            'link' => fake()->url(),
        ];
    }

    /**
     * Attach a sample picture, generated locally so a seed needs neither the
     * network nor a placeholder service — and only when a seeder asks for it,
     * because every image becomes a WebP conversion with responsive variants.
     */
    public function withImage(): static
    {
        return $this->afterCreating(function (Banner $banner) {
            $banner->addMedia(UploadedFile::fake()->image('banner.png', 1200, 800))->toMediaCollection('image');
        });
    }
}
