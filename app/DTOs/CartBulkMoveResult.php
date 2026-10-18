<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class CartBulkMoveResult
{
    private function __construct(
        public int $moved,
        public int $kept,
    ) {}

    public static function fromCounts(int $moved, int $kept): self
    {
        return new self($moved, $kept);
    }

    public function total(): int
    {
        return $this->moved + $this->kept;
    }

    public function isEmpty(): bool
    {
        return $this->total() === 0;
    }
}
