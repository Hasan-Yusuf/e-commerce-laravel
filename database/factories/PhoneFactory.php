<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Country;
use App\Models\Phone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Phone>
 */
class PhoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        $country = Country::query()->inRandomOrder()->first();

        if (! $country) {
            $country = Country::factory()->create();
        }

        /** @var Country $country */
        $phoneLength = $country->phone_length;

        return [
            'country_id' => $country->id,
            'phone_number' => fake()->numerify(
                str_repeat('#', $phoneLength),
            ),
        ];
    }
}
