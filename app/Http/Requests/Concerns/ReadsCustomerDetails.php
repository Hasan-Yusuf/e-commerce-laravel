<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\DTOs\CustomerData;

/**
 * The contact details a request carries. The checkout's customer step and
 * registration both collect a name and an email — the checkout also asks for a
 * phone number and the country it belongs to — so the field names, their
 * trimming and the shape they are read into live here rather than being written
 * out again in each request.
 */
trait ReadsCustomerDetails
{
    public function customer(): CustomerData
    {
        // Read through the DTO's own shape, so the country the number was given
        // for is resolved exactly as it is when the state is read back.
        return CustomerData::fromArray([
            'name' => $this->customerName(),
            'email' => $this->customerEmail(),
            'phone' => $this->customerPhone(),
            'country_id' => $this->input('country_id'),
        ]);
    }

    public function customerName(): string
    {
        return trim((string) $this->string('name'));
    }

    public function customerEmail(): string
    {
        return trim((string) $this->string('email'));
    }

    public function customerPhone(): string
    {
        return trim((string) $this->string('phone_number'));
    }
}
