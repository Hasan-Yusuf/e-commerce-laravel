<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\ShippingAddressData;
use App\Enums\AddressType;
use App\Models\Address;
use App\Models\Country;
use App\Models\User;
use App\Services\Account\AddressService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AddressServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_saves_the_address_as_a_shipping_address_on_the_account(): void
    {
        $user = User::factory()->create();
        $country = Country::factory()->bahrain()->create();

        $address = (new AddressService)->saveShippingAddress(
            $user,
            $this->address($country),
        );

        $this->assertModelExists($address);
        static::assertTrue($address->user->is($user));
        static::assertSame(AddressType::SHIPPING, $address->address_type);
        static::assertSame('12 Road 1234', $address->street_address);
        static::assertTrue($address->country->is($country));
    }

    public function test_it_reuses_an_address_the_account_already_has(): void
    {
        $user = User::factory()->create();
        $country = Country::factory()->bahrain()->create();
        $service = new AddressService;

        $first = $service->saveShippingAddress($user, $this->address($country));
        $second = $service->saveShippingAddress($user, $this->address($country));

        static::assertTrue($first->is($second));
        static::assertSame(1, Address::query()->count());
    }

    public function test_it_reads_the_address_the_account_last_gave(): void
    {
        $user = User::factory()->create();
        $country = Country::factory()->bahrain()->create();
        $service = new AddressService;

        // An account that has given nothing holds nothing, and reads back as a
        // blank address rather than as null.
        static::assertNull($service->shippingAddressFor($user));
        static::assertSame('', $service->shippingAddressDataFor($user)->streetAddress);

        $saved = $service->saveShippingAddress($user, $this->address($country));

        static::assertTrue($service->shippingAddressFor($user)?->is($saved));
        static::assertSame(
            '12 Road 1234',
            $service->shippingAddressDataFor($user)->streetAddress,
        );
    }

    public function test_it_edits_the_address_on_file_rather_than_adding_one(): void
    {
        $user = User::factory()->create();
        $country = Country::factory()->bahrain()->create();
        $service = new AddressService;

        $first = $service->updateShippingAddress($user, $this->address($country));

        $moved = $service->updateShippingAddress(
            $user,
            new ShippingAddressData(
                streetAddress: '5 Road 6789',
                city: 'Riffa',
                state: 'Southern',
                countryId: $country->id,
                postalCode: '5678',
            ),
        );

        // The same address moved house: one row, written over.
        static::assertTrue($first->is($moved));
        static::assertSame(1, Address::query()->count());
        static::assertSame('5 Road 6789', $moved->refresh()->street_address);
        static::assertSame(AddressType::SHIPPING, $moved->address_type);
    }

    private function address(Country $country): ShippingAddressData
    {
        return new ShippingAddressData(
            streetAddress: '12 Road 1234',
            city: 'Manama',
            state: 'Capital',
            countryId: $country->id,
            postalCode: '1234',
        );
    }
}
