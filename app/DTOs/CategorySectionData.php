<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Category;
use Illuminate\Support\Collection;

final readonly class CategorySectionData
{
    /**
     * @param  Collection<int, ProductCardData>  $products
     */
    public function __construct(
        public Category $category,
        public Collection $products,
    ) {}
}
