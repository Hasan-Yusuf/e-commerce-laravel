<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WishlistItem>
 */
class WishlistItemFactory extends Factory
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
            'product_id' => Product::factory(),
        ];
    }

    /**
     * Indicate that the item belongs to a signed-in shopper.
     */
    public function forUser(User $user): static
    {
        return $this->state(
            fn (): array => [
                'user_id' => $user->id,
                'session_id' => null,
            ],
        );
    }

    /**
     * Indicate that the item belongs to a guest session.
     */
    public function forSession(string $sessionId): static
    {
        return $this->state(
            fn (): array => [
                'user_id' => null,
                'session_id' => $sessionId,
            ],
        );
    }

    /**
     * Save an existing product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (): array => ['product_id' => $product->id]);
    }
}
