<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        $name = fake()->unique()->sentence(2);
        $parentCategory = Category::query()->inRandomOrder()->first()?->id;

        return [
            'slug' => Str::slug($name),

            'name_en' => Str::title($name),
            'name_ar' => Str::title($name),

            'description_en' => fake()->sentence(),
            'description_ar' => fake()->sentence(),

            'parent_id' => fake()->boolean(80) ? $parentCategory : null,
            'sort_order' => fake()->numberBetween(1, 20),
            'activated_at' => fake()->dateTimeBetween('now', '+30 days'),
        ];
    }

    /**
     * Indicate that the category is active.
     */
    public function active(): static
    {
        return $this->state(
            fn (): array => [
                'activated_at' => now()->subDay(),
            ],
        );
    }

    /* public function configure(): static
     * {
     * return $this->afterCreating(function ($category) {
     * $randomWidth = $this->faker->numberBetween(200, 800);
     * $randomHeight = $this->faker->numberBetween(200, 800);
     *
     * $category
     * ->addMediaFromUrl(
     * "https://picsum.photos/{$randomWidth}/{$randomHeight}",
     * )
     * ->toMediaCollection('thumbnail');
     * });
     * } */
}
