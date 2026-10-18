<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\WishlistItem;

/**
 * The outcome of a wishlist mutation (add / move), so callers can pick the
 * right feedback without re-reading the list or guessing what the service did.
 */
final readonly class WishlistItemResult
{
    private function __construct(
        public ?WishlistItem $item,
        public bool $alreadyExists,
        public bool $unavailable,
    ) {}

    /**
     * A new item was written.
     */
    public static function added(WishlistItem $item): self
    {
        return new self($item, false, false);
    }

    /**
     * The product was already saved. Adding is idempotent, so nothing changed.
     */
    public static function exists(WishlistItem $item): self
    {
        return new self($item, true, false);
    }

    /**
     * The product reference is gone, so nothing could be written.
     */
    public static function unavailable(?WishlistItem $item = null): self
    {
        return new self($item, false, true);
    }

    public function succeeded(): bool
    {
        return ! $this->unavailable;
    }
}
