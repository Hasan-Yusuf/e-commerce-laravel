<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\CacheKeys;
use App\Models\Category;
use App\Models\TypeSpec;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * The distinct values the catalogue's filterable list specs offer for one scope.
 *
 * A product's spec values live in a JSON column read per spec, so resolving them
 * spec by spec multiplies a listing's queries by its number of filters — on every
 * render and every filter tick. The whole scope's values are therefore resolved
 * in one batched query (SpecValueQuery) and kept under CacheKeys::SpecFilterValues,
 * keyed by category scope and locale; product, type and spec writes flush it
 * (SpecFilterValuesObserver).
 */
class SpecFilterValuesService
{
    public function __construct(
        private readonly SpecValueQuery $specValues,
    ) {}

    /**
     * @param  Collection<int, TypeSpec>  $specs
     * @return array<int, array<string, string>> Spec id => stored value => label.
     */
    public function for(
        Collection $specs,
        Builder $query,
        ?Category $category,
        string $locale,
    ): array {
        if ($specs->isEmpty()) {
            return [];
        }

        $scope = ($category?->getKey() ?? 'all').':'.$locale;

        $cached = Cache::get(CacheKeys::SpecFilterValues, []);
        $cached = is_array($cached) ? $cached : [];

        $cachedScope = $cached[$scope] ?? null;

        if (is_array($cachedScope)) {
            /** @var array<int, array<string, string>> $cachedScope */
            return $cachedScope;
        }

        $values = $this->specValues->distinctValuesFor($specs, $query, $locale);

        $cached[$scope] = $values;

        Cache::put(CacheKeys::SpecFilterValues, $cached, now()->addDay());

        return $values;
    }
}
