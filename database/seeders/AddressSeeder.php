<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AddressType;
use App\Enums\UserRole;
use App\Models\Address;
use App\Models\Country;
use App\Models\User;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    /**
     * Give most customers a separate billing address so orders can ship and
     * bill to different places, as they would in the real store.
     */
    public function run(): void
    {
        $bahrain = Country::query()->where('slug', 'bahrain')->first();

        if ($bahrain === null) {
            return;
        }

        User::query()
            ->where('role', UserRole::CUSTOMER)
            ->get()
            ->each(static function (User $user) use ($bahrain): void {
                $hasBilling = $user
                    ->addresses()
                    ->where('address_type', AddressType::BILLING)
                    ->exists();

                if ($hasBilling || ($user->id % 10) >= 7) {
                    return;
                }

                Address::create([
                    'user_id' => $user->id,
                    'address_type' => AddressType::BILLING,
                    'country_id' => $bahrain->id,
                    'defaulted_at' => null,
                    ...UserSeeder::bahrainAddress(),
                ]);
            });
    }
}
