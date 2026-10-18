<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\CartItem;
use App\Models\Product;

final readonly class CartLineData
{
    public function __construct(
        public CartItem $item,
        public ?Product $product,
        public int $quantity,
        public int $snapshotPriceInFils,
        public int $currentPriceInFils,
        public int $availableStock,
        public ?ResponsiveImageData $thumbnail,
    ) {}

    public function lineTotalInFils(): int
    {
        return $this->currentPriceInFils * $this->quantity;
    }

    public function isUnavailable(): bool
    {
        return $this->product === null || ! $this->product->isActive() || $this->availableStock <= 0;
    }

    public function isInactive(): bool
    {
        return $this->product !== null && ! $this->product->isActive();
    }

    public function priceChanged(): bool
    {
        return $this->product !== null && $this->currentPriceInFils !== $this->snapshotPriceInFils;
    }

    public function exceedsStock(): bool
    {
        return $this->product !== null && $this->quantity > $this->availableStock;
    }
}
