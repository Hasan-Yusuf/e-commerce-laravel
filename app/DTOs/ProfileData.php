<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * The details an account holds about a shopper: what the profile tab collects,
 * and what it writes back to the account.
 *
 * The shape is declared once here, so the request that collects these details
 * and the service that saves them agree on what a profile is.
 */
final readonly class ProfileData
{
    public function __construct(
        public string $name = '',
        public string $email = '',
        public string $locale = '',
        public ?int $countryId = null,
        public string $phoneNumber = '',
    ) {}

    /**
     * Read the details out of the validated input. Anything that is not an
     * array reads as a blank profile.
     */
    public static function fromArray(mixed $data): self
    {
        $data = is_array($data) ? $data : [];
        // The field the details form gives the phone's country: named for the
        // profile, because the address the account holds has a country of its own.
        $countryId = $data['profile_country_id'] ?? null;

        return new self(
            name: trim((string) ($data['name'] ?? '')),
            email: trim((string) ($data['email'] ?? '')),
            locale: trim((string) ($data['locale'] ?? '')),
            countryId: $countryId === null || $countryId === ''
                ? null
                : (int) $countryId,
            phoneNumber: trim((string) ($data['phone_number'] ?? '')),
        );
    }

    /**
     * Whether a phone was given. A number is only saved with the country it was
     * given for — it cannot be checked, or dialled, without one — but a country
     * on its own is not a number, so it is the pairing that answers this.
     */
    public function hasPhone(): bool
    {
        return $this->countryId !== null && $this->phoneNumber !== '';
    }
}
