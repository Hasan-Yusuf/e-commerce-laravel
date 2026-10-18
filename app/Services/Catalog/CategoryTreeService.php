<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\CacheKeys;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Reads the category tree: a category's ancestor chain, root-first, and its own
 * id together with every descendant's. Both walk a whole-tree query in memory
 * rather than following `parent` level by level, because the storefront asks
 * for them on every listing, search and breadcrumb.
 *
 * The whole tree is read once per request and kept in memory, so a page that
 * asks for ancestors in its header and its breadcrumbs does not load the table
 * again for each. The descendant ids are cached across requests until the tree
 * changes (see CategoryObserver), so the cache is read and written here rather
 * than in the model.
 */
class CategoryTreeService
{
    /**
     * The tree read for this request, keyed by id. Loaded once and shared by
     * ancestors() and computeDescendantIds().
     *
     * @var Collection<int, Category>|null
     */
    private ?Collection $tree = null;

    /**
     * The category's own id and the ids of all of its descendants.
     *
     * @return array<int, int>
     */
    public function descendantIds(Category $category): array
    {
        $categoryId = (int) $category->getKey();

        /** @var array<int, array<int, int>> $cached */
        $cached = Cache::get(CacheKeys::CategoryDescendantIds, []);

        if (array_key_exists($categoryId, $cached)) {
            return $cached[$categoryId];
        }

        $ids = $this->computeDescendantIds($category);

        $cached[$categoryId] = $ids;

        Cache::put(CacheKeys::CategoryDescendantIds, $cached, now()->addDay());

        return $ids;
    }

    /**
     * The category's ancestors, root-first.
     *
     * @return Collection<int, Category>
     */
    public function ancestors(Category $category): Collection
    {
        $byId = $this->tree();

        $ancestors = new Collection;

        /** @var array<int, true> $seen */
        $seen = [(int) $category->getKey() => true];
        $parentId = $category->parent_id === null ? null : (int) $category->parent_id;

        while ($parentId !== null && ! array_key_exists($parentId, $seen)) {
            $seen[$parentId] = true;

            $parent = $byId->get($parentId);

            if (! $parent instanceof Category) {
                break;
            }

            $ancestors->prepend($parent);
            $parentId = $parent->parent_id === null ? null : (int) $parent->parent_id;
        }

        return $ancestors;
    }

    /**
     * Drop the request's in-memory tree so the next read is fresh. Called when
     * a category is saved or removed (see CategoryObserver).
     */
    public function flush(): void
    {
        $this->tree = null;
    }

    /**
     * The whole tree, keyed by id. The columns are only what ancestors() and
     * computeDescendantIds() read, so the row is small even for a large tree.
     *
     * @return Collection<int, Category>
     */
    private function tree(): Collection
    {
        return $this->tree ??= Category::query()
            ->get(['id', 'parent_id', 'name_en', 'name_ar', 'slug'])
            ->keyBy('id');
    }

    /**
     * @return array<int, int>
     */
    private function computeDescendantIds(Category $category): array
    {
        $categoryId = (int) $category->getKey();

        $childrenByParent = $this->tree()->groupBy('parent_id');

        /** @var array<int, int> $ids */
        $ids = [$categoryId];
        /** @var array<int, true> $seen */
        $seen = [$categoryId => true];
        /** @var array<int, int> $frontier */
        $frontier = [$categoryId];

        while ($frontier !== []) {
            $next = [];

            foreach ($frontier as $parentId) {
                foreach ($childrenByParent->get($parentId, []) as $child) {
                    $childId = $child->getKey();

                    if (array_key_exists($childId, $seen)) {
                        continue;
                    }

                    $seen[$childId] = true;
                    $next[] = $childId;
                }
            }

            $frontier = $next;
            $ids = array_merge($ids, $frontier);
        }

        return array_values(array_unique($ids));
    }
}
