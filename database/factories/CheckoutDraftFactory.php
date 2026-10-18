<?php

declare(strict_types=1);

namespace Database\Factories;

use App\DTOs\CheckoutState;
use App\DTOs\CustomerData;
use App\DTOs\ShippingAddressData;
use App\Enums\CheckoutStep;
use App\Models\CheckoutDraft;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckoutDraft>
 */
class CheckoutDraftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * A shopper who answered the contact step and stopped on the delivery one,
     * with the address the account holds already filled in.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        $state = new CheckoutState(
            step: CheckoutStep::DELIVERY,
            progress: 1,
            customer: new CustomerData(
                name: 'Ada Lovelace',
                email: 'ada@example.com',
                phone: '39000000',
            ),
            shippingAddress: new ShippingAddressData(
                streetAddress: '12 Road 1234',
                city: 'Manama',
                state: 'Capital',
                postalCode: '1234',
            ),
        );

        return [
            'user_id' => User::factory(),
            'state' => $state->toArray(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(
            fn (array $attributes): array => ['user_id' => $user->id],
        );
    }
}
