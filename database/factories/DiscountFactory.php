<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
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
            'name' => fake()->sentence(2),

            'type' => DiscountType::PERCENTAGE,
            'value' => fake()->numberBetween(1, 50),

            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(3),

            'activated_at' => now()->subDay(),
        ];
    }

    /**
     * Indicate that the discount is a percentage off.
     */
    public function percentage(float $value = 10): static
    {
        return $this->state(
            fn (): array => [
                'type' => DiscountType::PERCENTAGE,
                'value' => $value,
            ],
        );
    }

    /**
     * Indicate that the discount is a fixed amount off, in decimal units.
     */
    public function fixedAmount(float $value = 1): static
    {
        return $this->state(
            fn (): array => [
                'type' => DiscountType::FIXED_AMOUNT,
                'value' => $value,
            ],
        );
    }

    /**
     * Indicate that the discount's validity window has already ended.
     */
    public function expired(): static
    {
        return $this->state(
            fn (): array => [
                'starts_at' => now()->subDays(5),
                'ends_at' => now()->subDay(),
            ],
        );
    }

    /**
     * Indicate that the discount's validity window has not started yet.
     */
    public function upcoming(): static
    {
        return $this->state(
            fn (): array => [
                'starts_at' => now()->addDay(),
                'ends_at' => now()->addDays(3),
            ],
        );
    }

    /**
     * Indicate that the discount has not been activated.
     */
    public function inactive(): static
    {
        return $this->state(
            fn (): array => [
                'activated_at' => null,
            ],
        );
    }

    /**
     * Indicate that the discount has no start or end date and stays active
     * once activated.
     */
    public function openEnded(): static
    {
        return $this->state(
            fn (): array => [
                'starts_at' => null,
                'ends_at' => null,
            ],
        );
    }
}
