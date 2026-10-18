<?php

declare(strict_types=1);

namespace App\Http\Requests\Checkout;

use App\Enums\DeliveryMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeliveryRequest extends FormRequest
{
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
            'delivery_method' => [
                Rule::required(),
                Rule::enum(DeliveryMethod::class),
            ],
        ];
    }

    /**
     * The method the shopper chose.
     */
    public function deliveryMethod(): DeliveryMethod
    {
        return DeliveryMethod::from((string) $this->string('delivery_method'));
    }
}
