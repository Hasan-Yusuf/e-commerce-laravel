<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * The outcome of moving the whole wishlist into the cart, so the caller can
 * word the feedback honestly: how many entries left the list, how many reached
 * the cart with a quantity trimmed to stock, and how many had to stay saved
 * because their product cannot be bought right now.
 */
final readonly class WishlistBulkMoveResult
{
    private function __construct(
        public int $moved,
        public int $clamped,
        public int $unavailable,
    ) {}

    public static function fromCounts(
        int $moved,
        int $clamped,
        int $unavailable,
    ): self {
        return new self($moved, $clamped, $unavailable);
    }

    /**
     * The entries the move looked at, whether or not they could be moved.
     */
    public function total(): int
    {
        return $this->moved + $this->unavailable;
    }

    /**
     * There was nothing on the wishlist to move.
     */
    public function isEmpty(): bool
    {
        return $this->total() === 0;
    }
}
