<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Product;
use App\Models\WishlistItem;

/**
 * A single wishlist entry prepared for display: the saved reference and the
 * card data for its product. A product that was removed from inventory leaves
 * a null product (and card), so the page can still offer to drop the entry.
 */
final readonly class WishlistLineData
{
    public function __construct(
        public WishlistItem $item,
        public ?Product $product,
        public ?ProductCardData $card,
    ) {}

    /**
     * The product was removed from inventory; only the reference is left.
     */
    public function isGone(): bool
    {
        return $this->product === null;
    }
}
