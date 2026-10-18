<?php

declare(strict_types=1);

namespace App\Http\Requests\Checkout;

use App\Http\Requests\Concerns\ReadsCustomerDetails;
use App\Rules\PhoneNumberForCountry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    use ReadsCustomerDetails;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [Rule::required(), Rule::string()->max(255)],
            'email' => [Rule::required(), Rule::string()->max(255), Rule::email()],
            'country_id' => [
                Rule::nullable(),
                Rule::requiredWith('phone_number'),
                Rule::numeric()->integer(),
                Rule::exists('countries', 'id'),
            ],
            'phone_number' => [
                Rule::nullable(),
                Rule::string()->max(32),
                Rule::requiredWith('country_id'),
                new PhoneNumberForCountry(
                    $this->integer('country_id'),
                ),
            ],
        ];
    }
}
