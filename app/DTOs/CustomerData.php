<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Country;

final readonly class CustomerData
{
    public function __construct(
        public string $name = '',
        public string $email = '',
        public string $phone = '',
        public ?int $countryId = null,
        public ?Country $country = null,
    ) {}

    public static function fromArray(mixed $data): self
    {
        $data = is_array($data) ? $data : [];
        $countryId = $data['country_id'] ?? null;
        $countryId =
            $countryId === null || $countryId === ''
                ? null
                : (int) $countryId;

        return new self(
            name: trim((string) ($data['name'] ?? '')),
            email: trim((string) ($data['email'] ?? '')),
            phone: trim((string) ($data['phone'] ?? '')),
            countryId: $countryId,
            country: $countryId === null
                ? null
                : Country::query()->find($countryId),
        );
    }

    /**
     * The shape the checkout state stores: the country the number was given for
     * under the name the step posts it, and the number as this DTO holds it.
     *
     * @return array{name: string, email: string, phone: string, country_id: int|null}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'country_id' => $this->countryId,
        ];
    }

    public function fullPhone(): string
    {
        if ($this->country === null) {
            return $this->phone;
        }

        return trim('+'.$this->country->calling_code.' '.$this->phone);
    }
}
