<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        $order = Order::query()->inRandomOrder()->first();

        if (! $order) {
            $order = Order::factory()->create();
        }

        /** @var Order $order */
        $product = Product::query()->inRandomOrder()->first();

        if (! $product) {
            $product = Product::factory()->create();
        }

        /** @var Product $product */
        $qty = fake()->numberBetween(1, 10);

        $unitPriceInFils = $product->price_in_fils->toFils();

        return [
            'order_id' => $order->id,

            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,

            'quantity' => $qty,

            'unit_price_in_fils' => $unitPriceInFils,
            'total_price_in_fils' => $unitPriceInFils * $qty,
        ];
    }

    public function withOrder(Order $order): static
    {
        return $this->state(
            fn (array $attributes) => ['order_id' => $order->id],
        );
    }

    public function withProduct(Product $product): static
    {
        return $this->state(
            fn (array $attributes) => ['product_id' => $product->id],
        );
    }
}
