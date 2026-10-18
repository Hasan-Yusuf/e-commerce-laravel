<?php

declare(strict_types=1);

namespace App\Http\Requests\Cart;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCartItemRequest extends FormRequest
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
            'product_id' => [
                Rule::required(),
                Rule::numeric()->integer(),
                Rule::exists('products', 'id')->where(
                    static fn (Builder $query): Builder => $query->whereNull('deleted_at')
                        ->where('activated_at', '<=', now()),
                ),
            ],
            'quantity' => [Rule::required(), Rule::numeric()->integer(true)->min(1)->max(999)],
        ];
    }
}
