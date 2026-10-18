<?php

declare(strict_types=1);

namespace App\Services\Catalog\Filters;

use App\Models\Category;
use App\Services\Catalog\CategoryTreeService;
use App\Services\Catalog\ProductFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CategoryFilter implements ProductFilter
{
    public function __construct(
        private CategoryTreeService $categoryTreeService,
    ) {}

    #[\Override]
    public function params(): array
    {
        return ['category'];
    }

    #[\Override]
    public function apply(Builder $query, array $params): void
    {
        $categoryIds = Collection::make($params['category'] ?? [])
            ->filter(static fn (mixed $id): bool => is_numeric($id))
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($categoryIds->isEmpty()) {
            return;
        }

        // Selecting a parent category also matches its descendants, matching
        // the category page behaviour.
        $categoryIds = Category::query()
            ->whereIn('id', $categoryIds->all())
            ->get()
            ->flatMap(
                fn (Category $category): array => $this->categoryTreeService->descendantIds($category),
            )
            ->unique()
            ->values()
            ->all();

        $query->whereHas(
            'type',
            static fn (Builder $types): Builder => $types->whereIn(
                'category_id',
                $categoryIds,
            ),
        );
    }
}
