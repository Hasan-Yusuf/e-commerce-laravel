<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

interface ProductFilter
{
    /**
     * The request parameters that activate this filter.
     *
     * @return array<int, string>
     */
    public function params(): array;

    /**
     * Apply the filter to the product query.
     *
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $params
     */
    public function apply(Builder $query, array $params): void;
}
