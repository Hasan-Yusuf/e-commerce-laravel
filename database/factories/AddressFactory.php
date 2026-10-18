<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AddressType;
use App\Enums\UserRole;
use App\Models\Address;
use App\Models\Country;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
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
            ->inRandomOrder()
            ->where('role', UserRole::CUSTOMER)
            ->first();

        if (! $user) {
            $user = User::factory()->create(['role' => UserRole::CUSTOMER]);
        }

        /** @var User $user */
        $country = Country::query()->inRandomOrder()->first();

        if (! $country) {
            $country = Country::factory()->create();
        }

        /** @var Country $country */
        return [
            'user_id' => $user->id,
            'address_type' => fake()->randomElement(AddressType::cases()),
            'street_address' => fake()->streetAddress(),
            'state' => fake()->city,
            'city' => fake()->city,
            'country_id' => $country->id,
            'postal_code' => fake()->postcode(),
        ];
    }

    public function user(User $user): static
    {
        return $this->state(
            fn (array $attributes) => [
                'user_id' => $user->id,
            ],
        );
    }

    public function country(Country $country): static
    {
        return $this->state(
            fn (array $attributes) => [
                'country_id' => $country->id,
            ],
        );
    }

    public function addressType(AddressType $addressType): static
    {
        return $this->state(
            fn (array $attributes) => ['address_type' => $addressType],
        );
    }
}
