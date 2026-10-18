<?php

declare(strict_types=1);

namespace App\Services\Catalog\Filters;

use App\Services\Catalog\ProductFilter;
use Illuminate\Database\Eloquent\Builder;

class AvailabilityFilter implements ProductFilter
{
    public const IN_STOCK = 'in_stock';

    #[\Override]
    public function params(): array
    {
        return ['availability'];
    }

    #[\Override]
    public function apply(Builder $query, array $params): void
    {
        if (($params['availability'] ?? null) !== self::IN_STOCK) {
            return;
        }

        $query->whereColumn('stock', '>', 'reserved_stock');
    }
}
