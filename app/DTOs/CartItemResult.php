<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\CartItem;

final readonly class CartItemResult
{
    private function __construct(
        public ?CartItem $item,
        public bool $clamped,
        public bool $unavailable,
    ) {}

    public static function accepted(CartItem $item, bool $clamped): self
    {
        return new self($item, $clamped, false);
    }

    public static function unavailable(?CartItem $item = null): self
    {
        return new self($item, false, true);
    }

    public function succeeded(): bool
    {
        return ! $this->unavailable;
    }
}
