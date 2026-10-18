<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
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
            'code' => mb_strtoupper(fake()->unique()->bothify('????-####')),

            'description' => fake()->sentence(3),

            'discount_type' => DiscountType::PERCENTAGE,
            'discount_value' => fake()->numberBetween(1, 50),
            'maximum_discount' => null,
            'min_order_amount' => 0,

            'usage_limit' => null,
            'used_count' => 0,

            'activated_at' => now()->subDay(),
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDays(3),
        ];
    }

    /**
     * Indicate that the coupon is a percentage off the subtotal.
     */
    public function percentage(float $value = 10): static
    {
        return $this->state(
            fn (): array => [
                'discount_type' => DiscountType::PERCENTAGE,
                'discount_value' => $value,
            ],
        );
    }

    /**
     * Indicate that the coupon is a fixed amount off, in BHD.
     */
    public function fixedAmount(float $value = 1): static
    {
        return $this->state(
            fn (): array => [
                'discount_type' => DiscountType::FIXED_AMOUNT,
                'discount_value' => $value,
            ],
        );
    }

    /**
     * Indicate that the coupon's validity window has already ended.
     */
    public function expired(): static
    {
        return $this->state(
            fn (): array => [
                'starts_at' => now()->subDays(5),
                'expires_at' => now()->subDay(),
            ],
        );
    }

    /**
     * Indicate that the coupon's validity window has not started yet.
     */
    public function upcoming(): static
    {
        return $this->state(
            fn (): array => [
                'starts_at' => now()->addDay(),
                'expires_at' => now()->addDays(3),
            ],
        );
    }

    /**
     * Indicate that the coupon has not been activated.
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
     * Indicate that the coupon accepts unlimited uses.
     */
    public function unlimited(): static
    {
        return $this->state(
            fn (): array => [
                'usage_limit' => null,
            ],
        );
    }

    /**
     * Indicate that the coupon has reached its usage limit.
     */
    public function exhausted(): static
    {
        return $this->state(
            fn (): array => [
                'usage_limit' => 1,
                'used_count' => 1,
            ],
        );
    }
}
