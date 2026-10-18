<?php

declare(strict_types=1);

namespace App\Services\Catalog\Filters;

use App\Services\Catalog\ProductFilter;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;

class PriceRangeFilter implements ProductFilter
{
    #[\Override]
    public function params(): array
    {
        return ['price_min', 'price_max'];
    }

    #[\Override]
    public function apply(Builder $query, array $params): void
    {
        $minimum = $this->toFils($params['price_min'] ?? null);
        $maximum = $this->toFils($params['price_max'] ?? null);

        if ($minimum !== null) {
            $query->where('price_in_fils', '>=', $minimum);
        }

        if ($maximum !== null) {
            $query->where('price_in_fils', '<=', $maximum);
        }
    }

    private function toFils(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return Money::fromBHD((float) $value)->toFils();
    }
}
