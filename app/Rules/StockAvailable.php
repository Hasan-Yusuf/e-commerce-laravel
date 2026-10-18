<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\OrderItem;
use App\Models\Product;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class StockAvailable implements ValidationRule
{
    public function __construct(
        protected Product $product,
        protected ?OrderItem $orderItem = null,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    #[\Override]
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail,
    ): void {
        $heldQuantity = $this->orderItem->quantity ?? 0;

        $maxQuantity = $this->product->getAvailableStock() + $heldQuantity;

        if ($value > $maxQuantity) {
            $fail('validation.stock_exceeded')->translate([
                'attribute' => $attribute,
                'max' => $maxQuantity,
            ]);
        }
    }
}
