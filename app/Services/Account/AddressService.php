<?php

declare(strict_types=1);

namespace App\Services\Account;

use App\DTOs\ShippingAddressData;
use App\Enums\AddressType;
use App\Models\Address;
use App\Models\User;

class AddressService
{
    public function shippingAddressFor(User $user): ?Address
    {
        return $user
            ->addresses()
            ->where('address_type', AddressType::SHIPPING)
            ->orderByDesc('defaulted_at')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();
    }

    public function shippingAddressDataFor(User $user): ShippingAddressData
    {
        $address = $this->shippingAddressFor($user);

        return $address === null
            ? new ShippingAddressData
            : ShippingAddressData::fromAddress($address);
    }

    public function saveShippingAddress(
        User $user,
        ShippingAddressData $address,
    ): Address {
        $attributes = [
            'address_type' => AddressType::SHIPPING->value,
            ...$address->toArray(),
        ];

        $existing = $user->addresses()->where($attributes)->first();

        if ($existing instanceof Address) {
            return $existing;
        }

        return $user->addresses()->create($attributes);
    }

    public function updateShippingAddress(
        User $user,
        ShippingAddressData $address,
    ): Address {
        $existing = $this->shippingAddressFor($user);

        if ($existing instanceof Address) {
            $existing->update($address->toArray());

            return $existing;
        }

        return $user->addresses()->create([
            'address_type' => AddressType::SHIPPING->value,
            ...$address->toArray(),
        ]);
    }
}
