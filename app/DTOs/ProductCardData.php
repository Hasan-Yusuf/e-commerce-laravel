<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Discount;
use App\Models\Product;
use App\Support\Money;

/**
 * What a product card shows: the pricing every surface agrees on, and the name
 * of the brand a shopper recognises the product by. Assembly lives in
 * ProductCardAssembler; this is only the shape it hands back.
 */
final readonly class ProductCardData
{
    public function __construct(
        public Product $product,
        public Money $currentPrice,
        public Money $originalPrice,
        public ?Discount $discount = null,
        public ?ResponsiveImageData $thumbnail = null,
        public ?string $brandName = null,
    ) {}

    public function isDiscounted(): bool
    {
        $isDiscounted = $this->discount !== null;

        return $isDiscounted && $this->originalPrice->greaterThan($this->currentPrice);
    }
}
