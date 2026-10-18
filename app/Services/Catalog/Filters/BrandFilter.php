<?php

declare(strict_types=1);

namespace App\Services\Catalog\Filters;

use App\Services\Catalog\ProductFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BrandFilter implements ProductFilter
{
    #[\Override]
    public function params(): array
    {
        return ['brand'];
    }

    #[\Override]
    public function apply(Builder $query, array $params): void
    {
        $brandIds = Collection::make($params['brand'] ?? [])
            ->filter(static fn (mixed $id): bool => is_numeric($id))
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($brandIds->isEmpty()) {
            return;
        }

        $query->whereIn('brand_id', $brandIds->all());
    }
}
