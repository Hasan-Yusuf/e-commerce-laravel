<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Address;
use App\Models\Country;

/**
 * The shipping address: what the address step collects, what the account can
 * fill in from an address already on file, and what the review prints.
 */
final readonly class ShippingAddressData
{
    public function __construct(
        public string $streetAddress = '',
        public string $city = '',
        public string $state = '',
        public ?int $countryId = null,
        public string $postalCode = '',
        // The country as it was resolved for display; `countryId` stays the
        // stored truth, so an address whose country was removed still reads its
        // id back.
        public ?Country $country = null,
    ) {}

    /**
     * Read the address out of the stored answers, resolving the country it
     * points at for display. Anything that is not an array (a stale or tampered
     * state) reads as a blank address.
     */
    public static function fromArray(mixed $data): self
    {
        $data = is_array($data) ? $data : [];
        $countryId = $data['country_id'] ?? null;

        return new self(
            streetAddress: trim((string) ($data['street_address'] ?? '')),
            city: trim((string) ($data['city'] ?? '')),
            state: trim((string) ($data['state'] ?? '')),
            countryId: $countryId === null ? null : (int) $countryId,
            postalCode: trim((string) ($data['postal_code'] ?? '')),
            country: $countryId !== null
                ? Country::query()->find((int) $countryId)
                : null,
        );
    }

    /**
     * Read an address the account holds: how a saved address and the address an
     * order was shipped to are printed back.
     */
    public static function fromAddress(Address $address): self
    {
        return new self(
            streetAddress: $address->street_address,
            city: $address->city,
            state: $address->state,
            countryId: $address->country_id,
            postalCode: $address->postal_code,
            country: $address->country,
        );
    }

    /**
     * The shape the checkout state stores — the same keys the address form
     * posts, so the state can be handed straight back to it.
     *
     * @return array{street_address: string, city: string, state: string, country_id: int|null, postal_code: string}
     */
    public function toArray(): array
    {
        return [
            'street_address' => $this->streetAddress,
            'city' => $this->city,
            'state' => $this->state,
            'country_id' => $this->countryId,
            'postal_code' => $this->postalCode,
        ];
    }

    /**
     * The address as display lines: street, city and state, then the country
     * with the postal code. Empty parts are dropped, so a partly filled address
     * still reads as one.
     *
     * @return array<int, string>
     */
    public function lines(): array
    {
        return array_values(
            array_filter([
                $this->streetAddress,
                $this->join([$this->city, $this->state], ', '),
                $this->join(
                    [$this->country->name ?? '', $this->postalCode],
                    ' ',
                ),
            ]),
        );
    }

    /**
     * @param  array<int, string>  $parts
     */
    private function join(array $parts, string $separator): string
    {
        $parts = array_filter(
            array_map(static fn (string $part): string => trim($part), $parts),
            static fn (string $part): bool => $part !== '',
        );

        return implode($separator, $parts);
    }
}
