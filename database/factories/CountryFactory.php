<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        $name = fake()->unique()->country;

        return [
            'name_en' => $name,
            'name_ar' => $name,
            'slug' => str()->slug($name),
            'calling_code' => (string) fake()->numberBetween(1, 999),
            'phone_length' => fake()->numberBetween(8, 12),
        ];
    }

    public function bahrain(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'name_en' => 'Bahrain',
                'name_ar' => 'بحرين',
                'slug' => 'bahrain',
                'calling_code' => '973',
                'phone_length' => 8,
            ],
        );
    }
}
