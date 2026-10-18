<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CacheKeys;
use App\Models\Category;
use App\Services\Catalog\CategoryTreeService;
use Illuminate\Support\Facades\Cache;

class CategoryObserver
{
    /**
     * The category tree changed, so any cached descendant id lists and the
     * filter values scoped by category are stale.
     */
    public function saved(Category $category): void
    {
        $this->forgetCachedTrees();
    }

    public function deleted(Category $category): void
    {
        $this->forgetCachedTrees();
    }

    private function forgetCachedTrees(): void
    {
        Cache::forget(CacheKeys::CategoryDescendantIds);
        Cache::forget(CacheKeys::SpecFilterValues);

        // The request-scoped tree in CategoryTreeService holds the same data
        // and is cleared too, so a category saved and read in one request does
        // not serve a stale copy.
        app(CategoryTreeService::class)->flush();
    }
}
