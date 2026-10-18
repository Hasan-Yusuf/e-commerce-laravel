<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\Catalog\CategoryService;
use App\Services\Catalog\ProductQueryService;
use Illuminate\View\View;

final class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(CategoryService $categoryService): View
    {
        return view('category.index', [
            'sections' => $categoryService->getCategorySections(),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * The category's own furniture — its name, description, children and total —
     * is read here; the listing under it is the catalog component's, which is
     * told only the category it sits inside.
     */
    public function show(
        Category $category,
        ProductQueryService $productQuery,
    ): View {
        $category->loadMissing([
            'parent',
            'children' => static fn ($query) => $query->active()->orderBy('sort_order'),
        ]);

        return view('category.show', [
            'category' => $category,
            'totalProducts' => $productQuery->baseQuery($category)->count(),
        ]);
    }
}
