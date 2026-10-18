<?php

declare(strict_types=1);

namespace App\Services\Account;

use App\DTOs\ProfileData;
use App\Models\Phone;
use App\Models\User;

class AccountService
{
    public function updateProfile(User $user, ProfileData $profile): User
    {
        $user->update([
            'name' => $profile->name,
            'email' => $profile->email,
            'locale' => $profile->locale,
            'country_id' => $profile->countryId,
            'phone_id' => $this->phoneIdFor($profile),
        ]);

        return $user;
    }

    public function rememberLocale(User $user, string $locale): void
    {
        $user->update(['locale' => $locale]);
    }

    public function updatePassword(User $user, #[\SensitiveParameter] string $password): void
    {
        $user->update(['password' => $password]);
    }

    private function phoneIdFor(ProfileData $profile): ?int
    {
        if (! $profile->hasPhone()) {
            return null;
        }

        return Phone::query()
            ->firstOrCreate([
                'country_id' => $profile->countryId,
                'phone_number' => $profile->phoneNumber,
            ])
            ->getKey();
    }
}
