<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Order;
use LogicException;

final readonly class OrderPlacementResult
{
    private function __construct(
        public ?Order $order,
        public bool $stockChanged,
    ) {}

    public static function placed(Order $order): self
    {
        return new self($order, false);
    }

    public static function stockChanged(): self
    {
        return new self(null, true);
    }

    public function wasPlaced(): bool
    {
        return $this->order !== null;
    }

    /**
     * The order the placement produced. Only a stock rejection carries none, so
     * a caller that has already handled that has an order to read here.
     */
    public function orderOrFail(): Order
    {
        return
            $this->order ?? throw new LogicException(
                'The placement produced no order because stock moved.',
            );
    }
}
