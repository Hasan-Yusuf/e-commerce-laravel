<?php

declare(strict_types=1);

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
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
            'checkout_token' => [Rule::nullable(), Rule::string()->max(255)],
            'notes' => [Rule::nullable(), Rule::string()->max(1000)],
        ];
    }

    public function token(): string
    {
        return (string) $this->string('checkout_token');
    }

    public function note(): ?string
    {
        $note = $this->string('notes')->squish()->toString();

        return $note === '' ? null : $note;
    }
}
