<?php

declare(strict_types=1);

namespace App\Http\Requests\Checkout;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The method is validated against the PaymentMethod enum; the registry
     * supplies the driver that charges it once the order exists.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'payment_method' => [
                Rule::required(),
                Rule::string(),
                Rule::enum(PaymentMethod::class),
            ],
        ];
    }

    /**
     * The method the shopper chose.
     */
    public function paymentMethod(): PaymentMethod
    {
        return PaymentMethod::from((string) $this->string('payment_method'));
    }
}
