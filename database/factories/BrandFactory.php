<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
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
            'name_en' => fake()->word(),
            'name_ar' => fake()->word(),
        ];
    }

    /* public function configure(): static
     * {
     * return $this->afterCreating(function ($brand) {
     * $randomWidth = $this->faker->numberBetween(150, 200);
     * $randomHeight = $this->faker->numberBetween(150, 200);
     *
     * $brand
     * ->addMediaFromUrl(
     * "https://picsum.photos/{$randomWidth}/{$randomHeight}",
     * )
     * ->toMediaCollection('thumbnail');
     * });
     * } */
}
