<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AddressType;
use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        $user = User::query()
            ->where('role', UserRole::CUSTOMER)
            ->with('addresses')
            ->inRandomOrder()
            ->first();

        if (! $user) {
            $user = User::factory()->create(['role' => UserRole::CUSTOMER]);
        }

        /** @var User $user */
        $shippingAddressId = null;
        $billingAddressId = null;
        $addressesCount = $user->addresses->count();

        if ($addressesCount >= 1) {
            $defaultAddress = $user
                ->addresses
                ->whereNotNull('defaulted_at')
                ->first();

            $billingAddressId = $defaultAddress !== null
                ? $defaultAddress->id
                : $user->addresses->first()?->id;

            $shippingAddress = $user
                ->addresses
                ->where('address_type', AddressType::SHIPPING)
                ->first();

            $shippingAddressId = $shippingAddress !== null
                ? $shippingAddress->id
                : $user->addresses->last()?->id;
        }

        if ($addressesCount === 0) {
            /** @var Address $billingAddress */
            $billingAddress = Address::factory()
                ->user($user)
                ->create([
                    'address_type' => AddressType::BILLING,
                    'defaulted_at' => now(),
                ]);

            $billingAddressId = $billingAddress->id;

            /** @var Address $shippingAddress */
            $shippingAddress = Address::factory()
                ->user($user)
                ->create([
                    'address_type' => AddressType::SHIPPING,
                    'defaulted_at' => null,
                ]);

            $shippingAddressId = $shippingAddress->id;
        }

        $subtotal = fake()->randomNumber(3, true);
        $tax = $subtotal * 0.15;
        $shipping = fake()->randomElement([0, 10, 15, 25]);
        $discount = fake()->randomElement([0, 0, 5, 10]);
        $total = $subtotal + $tax + $shipping - $discount;

        return [
            'user_id' => $user->id,

            'status' => fake()->randomElement(OrderStatus::cases()),

            // Most orders are delivered; a collection is asked for explicitly
            // with the `pickup()` state, so an order's shape stays predictable.
            'delivery_method' => DeliveryMethod::STANDARD,

            'number' => 'ORD-'.str_pad((string) fake()->randomNumber(8), 8, '0', STR_PAD_LEFT),

            // The tokens an order carries: the checkout attempt that placed it
            // and the one that opens its confirmation.
            'checkout_token' => (string) Str::uuid(),
            'access_token' => (string) Str::uuid(),

            'subtotal_in_fils' => Money::fromBHD($subtotal),
            'tax_amount_in_fils' => Money::fromBHD($tax),
            'shipping_amount_in_fils' => Money::fromBHD($shipping),
            'discount_amount_in_fils' => Money::fromBHD($discount),
            'total_amount_in_fils' => Money::fromBHD($total),

            'shipping_address_id' => $shippingAddressId,
            'billing_address_id' => $billingAddressId,

            'notes' => fake()->optional(0.3)->sentence(),
            'paid_at' => fake()->optional(0.7)->dateTimeBetween('-1 month', 'now'),

            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function paid(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'paid_at' => now(),
                'status' => OrderStatus::PROCESSING,
            ],
        );
    }

    /**
     * Indicate that the order is collected from the store rather than delivered.
     */
    public function pickup(): static
    {
        return $this->state(fn (): array => [
            'delivery_method' => DeliveryMethod::PICKUP,
        ]);
    }
}
