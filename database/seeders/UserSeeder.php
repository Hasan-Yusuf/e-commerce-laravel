<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AddressType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Address;
use App\Models\Country;
use App\Models\Phone;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Representative customer profiles; the rest of the customers are
     * generated with Faker for volume.
     *
     * @var array<int, array{name: string, email: string, locale: string}>
     */
    private const CUSTOMERS = [
        [
            'name' => 'Alex Morgan',
            'email' => 'alex.morgan@example.com',
            'locale' => 'en',
        ],
        [
            'name' => 'Samira Hassan',
            'email' => 'samira.hassan@example.com',
            'locale' => 'ar',
        ],
        [
            'name' => 'Omar Farouk',
            'email' => 'omar.farouk@example.com',
            'locale' => 'en',
        ],
        [
            'name' => 'Nora Khalil',
            'email' => 'nora.khalil@example.com',
            'locale' => 'ar',
        ],
        [
            'name' => 'Daniel Brooks',
            'email' => 'daniel.brooks@example.com',
            'locale' => 'en',
        ],
    ];

    /**
     * Delivery areas by governorate, for representative delivery addresses.
     *
     * @var array<int, array{city: string, state: string}>
     */
    public const BAHRAIN_AREAS = [
        ['city' => 'Manama', 'state' => 'Capital'],
        ['city' => 'Juffair', 'state' => 'Capital'],
        ['city' => 'Seef', 'state' => 'Capital'],
        ['city' => 'Adliya', 'state' => 'Capital'],
        ['city' => 'Sitra', 'state' => 'Capital'],
        ['city' => 'Tubli', 'state' => 'Capital'],
        ['city' => 'Riffa', 'state' => 'Southern'],
        ['city' => 'Isa Town', 'state' => 'Southern'],
        ['city' => 'Hamad Town', 'state' => 'Southern'],
        ['city' => 'Zallaq', 'state' => 'Southern'],
        ['city' => 'Muharraq', 'state' => 'Muharraq'],
        ['city' => 'Arad', 'state' => 'Muharraq'],
        ['city' => 'Busaiteen', 'state' => 'Muharraq'],
        ['city' => 'Hidd', 'state' => 'Muharraq'],
        ['city' => 'Budaiya', 'state' => 'Northern'],
    ];

    /**
     * Build a representative address line (building, road, block).
     *
     * @return array{street_address: string, state: string, city: string, postal_code: string}
     */
    public static function bahrainAddress(): array
    {
        /** @var array{city: string, state: string} $area */
        $area = fake()->randomElement(self::BAHRAIN_AREAS);

        return [
            'street_address' => sprintf(
                'Building %d, Road %d, Block %d',
                fake()->numberBetween(1, 999),
                fake()->numberBetween(1, 3999),
                fake()->numberBetween(101, 999),
            ),
            'state' => $area['state'],
            'city' => $area['city'],
            'postal_code' => (string) fake()->numberBetween(100, 999),
        ];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedAdmins();
        $this->seedCustomers();
    }

    private function seedAdmins(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Nexus Administrator',
                'role' => UserRole::ADMIN,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
                'locale' => 'en',
                'password' => Hash::make('password'),
            ],
        );

        User::updateOrCreate(
            ['email' => 'admin-ar@example.com'],
            [
                'name' => 'Nexus Administrator',
                'role' => UserRole::ADMIN,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
                'locale' => 'ar',
                'password' => Hash::make('password'),
            ],
        );
    }

    private function seedCustomers(): void
    {
        foreach (self::CUSTOMERS as $customer) {
            $user = User::updateOrCreate(
                ['email' => $customer['email']],
                [
                    'name' => $customer['name'],
                    'role' => UserRole::CUSTOMER,
                    'status' => UserStatus::ACTIVE,
                    'email_verified_at' => now()->subMonths(
                        fake()->numberBetween(1, 18),
                    ),
                    'locale' => $customer['locale'],
                    'password' => Hash::make('password'),
                ],
            );

            $this->attachContactDetails($user);
        }

        $target = count(self::CUSTOMERS) + 7;

        $missing = max(
            0,
            $target - User::query()->where('role', UserRole::CUSTOMER)->count(),
        );

        if ($missing === 0) {
            return;
        }

        User::factory()
            ->count($missing)
            ->create([
                'role' => UserRole::CUSTOMER,
                'status' => UserStatus::ACTIVE,
                'locale' => fake()->randomElement(['en', 'ar']),
            ])
            ->each($this->attachContactDetails(...));
    }

    /**
     * Give a customer a phone number and a default shipping address,
     * unless they already have one (keeps reseeding idempotent).
     */
    private function attachContactDetails(User $user): void
    {
        $bahrain = Country::query()->where('slug', 'bahrain')->first();

        if ($bahrain === null) {
            return;
        }

        if ($user->phone_id === null) {
            /** @var Phone $phone */
            $phone = Phone::create([
                'country_id' => $bahrain->id,
                'phone_number' => fake()->numerify('3#######'),
            ]);

            $user->update(['phone_id' => $phone->id]);
        }

        // The account's country is its own, and the one its number was given for
        // is the same.
        if ($user->country_id === null) {
            $user->update(['country_id' => $bahrain->id]);
        }

        if ($user->addresses()->doesntExist()) {
            Address::create([
                'user_id' => $user->id,
                'address_type' => AddressType::SHIPPING,
                'country_id' => $bahrain->id,
                'defaulted_at' => now()->subDays(fake()->numberBetween(1, 120)),
                ...self::bahrainAddress(),
            ]);
        }
    }
}
