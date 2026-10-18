<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cart;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
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
            'user_id' => null,
            'session_id' => Str::random(40),
            'expires_at' => now()->addDays(CartService::GUEST_LIFETIME_DAYS),
        ];
    }

    /**
     * Indicate that the cart belongs to a user.
     */
    public function forUser(User $user): static
    {
        return $this->state(
            fn (): array => [
                'user_id' => $user->id,
                'session_id' => null,
                'expires_at' => now()->addDays(CartService::USER_LIFETIME_DAYS),
            ],
        );
    }

    /**
     * Indicate that the cart has already expired.
     */
    public function expired(): static
    {
        return $this->state(
            fn (): array => [
                'expires_at' => now()->subDay(),
            ],
        );
    }
}
