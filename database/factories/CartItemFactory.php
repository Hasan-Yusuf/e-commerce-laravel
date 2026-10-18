<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartItem>
 */
class CartItemFactory extends Factory
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
            'cart_id' => Cart::factory(),
            'product_id' => Product::factory(),
            'quantity' => 1,
            'price_in_fils' => fake()->numberBetween(1_000, 100_000),
        ];
    }

    /**
     * Attach the line to an existing cart.
     */
    public function forCart(Cart $cart): static
    {
        return $this->state(fn (): array => ['cart_id' => $cart->id]);
    }

    /**
     * Attach the line to an existing product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (): array => ['product_id' => $product->id]);
    }
}
