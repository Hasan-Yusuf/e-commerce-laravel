<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\DTOs\ShippingAddressData;
use Illuminate\Validation\Rule;

/**
 * The shipping address a request carries: the five fields the address form
 * posts, validated the one way — the checkout's shipping step and the account's
 * profile both save the address an order is delivered to.
 */
trait ReadsShippingAddress
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function shippingAddressRules(): array
    {
        return [
            'street_address' => [Rule::required(), Rule::string()->max(255)],
            'city' => [Rule::required(), Rule::string()->max(255)],
            'state' => [Rule::required(), Rule::string()->max(255)],
            'country_id' => [
                Rule::required(),
                Rule::exists('countries', 'id'),
            ],
            'postal_code' => [Rule::required(), Rule::string()->max(20)],
        ];
    }

    /**
     * The address itself, without any choice a form adds around it.
     */
    public function address(): ShippingAddressData
    {
        return ShippingAddressData::fromArray($this->validated());
    }
}
