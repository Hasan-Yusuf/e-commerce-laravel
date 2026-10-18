<?php

declare(strict_types=1);

namespace App\Http\Requests\Checkout;

use App\Http\Requests\Concerns\ReadsShippingAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShippingAddressRequest extends FormRequest
{
    use ReadsShippingAddress;

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
            ...$this->shippingAddressRules(),

            // Only offered to signed-in shoppers, so it is only ever present
            // when there is an account to save the address to.
            'save_to_account' => [Rule::nullable(), Rule::boolean()],
        ];
    }
}
