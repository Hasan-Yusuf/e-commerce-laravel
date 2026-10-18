<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Http\Requests\Concerns\ReadsShippingAddress;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The account's delivery address, which the profile answers in a form of its
 * own: the address an order is delivered to, whole.
 */
class UpdateAddressRequest extends FormRequest
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
        return $this->shippingAddressRules();
    }
}
