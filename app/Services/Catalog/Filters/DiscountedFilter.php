<?php

declare(strict_types=1);

namespace App\Services\Catalog\Filters;

use App\Services\Catalog\ProductFilter;
use Illuminate\Database\Eloquent\Builder;

class DiscountedFilter implements ProductFilter
{
    #[\Override]
    public function params(): array
    {
        return ['discounted'];
    }

    #[\Override]
    public function apply(Builder $query, array $params): void
    {
        if (! filter_var($params['discounted'] ?? false, FILTER_VALIDATE_BOOL)) {
            return;
        }

        $query->discounted();
    }
}
