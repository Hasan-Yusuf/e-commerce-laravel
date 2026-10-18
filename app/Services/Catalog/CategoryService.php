<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\DTOs\CategorySectionData;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

class CategoryService
{
    /**
     * The number of category sections shown per page.
     */
    public const int SECTIONS_PER_PAGE = 4;

    /**
     * The number of products previewed inside each category section.
     */
    public const int PRODUCTS_PER_SECTION = 4;

    /**
     * The number of categories the homepage rail holds.
     */
    public const int CATEGORIES_PER_SECTION = 8;

    public function __construct(
        private ProductQueryService $productQuery,
        private ProductCardAssembler $cardAssembler,
    ) {}

    public function getCategories(): Collection
    {
        $categories = Category::active()->orderBy('sort_order')->get();

        return $this->buildCategoryTree($categories, null);
    }

    /**
     * The top-level active categories the homepage offers, in the order the
     * admin set, with the thumbnail their cards show.
     *
     * A rail is a flat row, so the tree `getCategories()` builds for the header
     * is not what it wants: only the top level, and only as many as fit.
     *
     * @return Collection<int, Category>
     */
    public function getHomePageCategories(
        int $count = self::CATEGORIES_PER_SECTION,
    ): Collection {
        return Category::active()
            ->with('media')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->limit($count)
            ->get();
    }

    public function getCategorySections(): LengthAwarePaginator
    {
        return Category::active()
            ->with(['children' => static fn (Relation $query) => $query->active()->orderBy('sort_order')])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->paginate(self::SECTIONS_PER_PAGE)
            ->through(
                fn (
                    Category $category,
                ): CategorySectionData => new CategorySectionData(
                    $category,
                    $this->cardAssembler->fromProducts(
                        $this->productQuery
                            ->baseQuery($category)
                            ->latest()
                            ->limit(self::PRODUCTS_PER_SECTION)
                            ->get(),
                    ),
                ),
            );
    }

    /**
     * Build the category tree from a flat list. The list is grouped by parent
     * once rather than re-filtered per node, so the whole tree is one pass
     * instead of scanning every category for every node.
     *
     * @param  Collection<int, Category>  $categories
     * @return Collection<int, Category>
     */
    private function buildCategoryTree(
        Collection $categories,
        ?int $parentId,
    ): Collection {
        $childrenByParent = $categories->groupBy(
            static fn (Category $category): string => $category->parent_id === null
                ? 'root'
                : (string) $category->parent_id,
        );

        return $this->buildBranch($childrenByParent, $parentId);
    }

    /**
     * @param  Collection<string, Collection<int, Category>>  $childrenByParent
     * @return Collection<int, Category>
     */
    private function buildBranch(
        Collection $childrenByParent,
        ?int $parentId,
    ): Collection {
        $key = $parentId === null ? 'root' : (string) $parentId;

        return $childrenByParent
            ->get($key, new Collection)
            ->map(function (Category $category) use ($childrenByParent): Category {
                $category->children = $this->buildBranch(
                    $childrenByParent,
                    (int) $category->id,
                );

                return $category;
            })
            ->values();
    }
}
