<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Category;
use App\Services\Catalog\CategoryService;
use App\Services\Catalog\CategoryTreeService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

class Header extends Component
{
    public Collection $categories;

    public CategoryService $categoryService;

    /**
     * The category bound to the current route, when browsing a category page.
     */
    public ?int $activeCategoryId = null;

    /**
     * The top-level category the current route's category belongs to, so
     * browsing a subcategory still marks its parent link as selected.
     */
    public ?int $activeAncestorId = null;

    public function __construct(
        CategoryService $categoryService,
        private readonly CategoryTreeService $categoryTreeService,
        ?Collection $categories = null,
    ) {
        $this->categoryService = $categoryService;
        $this->categories = $categories?->isNotEmpty()
            ? $categories
            : $this->categoryService->getCategories();

        $this->resolveActiveCategory();
    }

    /**
     * Point the navigation at the category shown by the current route.
     */
    private function resolveActiveCategory(): void
    {
        if (! request()->routeIs('category.show')) {
            return;
        }

        $category = request()->route('category');

        if (! $category instanceof Category) {
            return;
        }

        $this->activeCategoryId = (int) $category->getKey();
        $this->activeAncestorId = $this->topLevelAncestorId($category);
    }

    /**
     * The top-level ancestor a category belongs to, resolved from the ancestor
     * chain the model reads in one query.
     */
    private function topLevelAncestorId(Category $category): int
    {
        return (int) ($this->categoryTreeService->ancestors($category)->first()?->getKey() ?? $category->getKey());
    }

    #[\Override]
    public function render()
    {
        return view('layouts.components.header');
    }
}
