<?php

declare(strict_types=1);

namespace App\Http\Requests\Wishlist;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWishlistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Saving is limited to live products (not soft-deleted, already activated);
     * out-of-stock products are allowed, since a wishlist is a save-for-later.
     *
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
        ];
    }
}
