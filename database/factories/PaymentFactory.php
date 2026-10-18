<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        $order = Order::count() > 0 ? Order::query()->inRandomOrder()->first() : Order::factory()->create();

        /** @var Order|null $order */
        return [
            'order_id' => $order?->id,
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'status' => fake()->randomElement(PaymentStatus::cases()),
            'amount_in_fils' => fake()->numberBetween(1_000, 500_000),
            'paid_at' => null,
            'meta' => null,
        ];
    }

    public function paidFor(Order $order): static
    {
        return $this->state(
            fn (array $attributes): array => ['order_id' => $order->id],
        );
    }

    /**
     * A payment that has been settled: a collection made on delivery of an
     * earlier order.
     */
    public function paid(): static
    {
        return $this->state(
            fn (array $attributes): array => [
                'status' => PaymentStatus::PAID,
                'paid_at' => now(),
            ],
        );
    }

    public function status(PaymentStatus $status): static
    {
        return $this->state(
            fn (array $attributes): array => ['status' => $status],
        );
    }

    public function method(PaymentMethod $method): static
    {
        return $this->state(
            fn (array $attributes): array => ['method' => $method],
        );
    }
}
