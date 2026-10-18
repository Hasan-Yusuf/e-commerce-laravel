<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\DTOs\SpecFilterData;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\TypeSpec;
use App\Services\Catalog\Filters\AvailabilityFilter;
use App\Services\Catalog\Filters\BrandFilter;
use App\Services\Catalog\Filters\CategoryFilter;
use App\Services\Catalog\Filters\DiscountedFilter;
use App\Services\Catalog\Filters\PriceRangeFilter;
use App\Services\Catalog\Filters\SpecFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;

class ProductQueryService
{
    public const string DEFAULT_SORT = 'newest';

    public function __construct(
        private readonly SpecFilterValuesService $specValuesService,
        private readonly CategoryTreeService $categoryTreeService,
    ) {}

    /**
     * The whitelist of supported sort keys and their column/direction.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const array SORTS = [
        'newest' => ['created_at', 'desc'],
        'price_asc' => ['price_in_fils', 'asc'],
        'price_desc' => ['price_in_fils', 'desc'],
        'name' => ['name', 'asc'],
    ];

    /**
     * The filter registry. Add a new filter class here (e.g. specs filters)
     * and it plugs into the pipeline automatically.
     *
     * @var array<class-string<ProductFilter>>
     */
    protected const array FILTERS = [
        BrandFilter::class,
        CategoryFilter::class,
        PriceRangeFilter::class,
        AvailabilityFilter::class,
        DiscountedFilter::class,
        SpecFilter::class,
    ];

    /**
     * Per-instance cache of the supported request parameters.
     *
     * @var array<int, string>|null
     */
    private ?array $supportedParams = null;

    /**
     * Extract the catalog parameters from a request. Because the list is
     * derived from the filter registry, every page (category, product index,
     * later search) accepts exactly the same filters and sort keys.
     *
     * @return array<string, mixed>
     */
    public function requestParams(Request $request): array
    {
        return $request->only($this->supportedParams());
    }

    /**
     * The request parameters the pipeline understands: the sort key plus every
     * parameter exposed by a registered filter.
     *
     * @return array<int, string>
     */
    public function supportedParams(): array
    {
        if ($this->supportedParams !== null) {
            return $this->supportedParams;
        }

        $params = ['sort'];

        foreach (self::FILTERS as $filterClass) {
            $filter = app($filterClass);

            $params = array_merge($params, $filter->params());
        }

        return $this->supportedParams = array_values(array_unique($params));
    }

    /**
     * A fresh active-product query with the optional category scope applied.
     *
     * Only what a card reads is eager loaded: every card shows a thumbnail and
     * names the brand it belongs to, while the type is the list view's alone
     * and the category is nobody here's — a caller that needs them asks for
     * them.
     *
     * @return Builder<Product>
     */
    public function baseQuery(?Category $category = null): Builder
    {
        $query = Product::query()
            ->active()
            ->with(['media', 'brand']);

        if ($category !== null) {
            $categoryIds = $this->categoryTreeService->descendantIds($category);

            $query->whereHas(
                'type',
                static fn (Builder $types): Builder => $types->whereIn(
                    'category_id',
                    $categoryIds,
                ),
            );
        }

        return $query;
    }

    /**
     * Build the product query from the given (already whitelisted) request
     * parameters.
     *
     * @param  array<string, mixed>  $params
     * @return Builder<Product>
     */
    public function query(array $params, ?Category $category = null): Builder
    {
        $query = $this->baseQuery($category);

        $this->applyFilters($query, $params);
        $this->applySort($query, $params['sort'] ?? null);

        return $query;
    }

    /**
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $params
     */
    public function applyFilters(Builder $query, array $params): void
    {
        foreach (self::FILTERS as $filterClass) {
            $filter = app($filterClass);

            if (
                array_intersect($filter->params(), array_keys($params)) === []
            ) {
                continue;
            }

            $filter->apply($query, $params);
        }
    }

    /**
     * The validated sort key for the given params (falls back to the default).
     *
     * @param  array<string, mixed>  $params
     */
    public function sortKey(array $params): string
    {
        return $this->normalizeSort($params['sort'] ?? null);
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function applySort(Builder $query, mixed $sort): void
    {
        [$column, $direction] = self::SORTS[$this->normalizeSort($sort)];

        $query->orderBy($column, $direction);
    }

    private function normalizeSort(mixed $sort): string
    {
        return is_string($sort) && array_key_exists($sort, self::SORTS)
            ? $sort
            : self::DEFAULT_SORT;
    }

    /**
     * Brands that have active products within the category scope.
     *
     * @return Collection<int, Brand>
     */
    public function brandOptions(?Category $category = null): Collection
    {
        $categoryIds = $category === null
            ? []
            : $this->categoryTreeService->descendantIds($category);

        return Brand::query()
            ->whereHas('products', static function (Builder $products) use ($category, $categoryIds) {
                $products->active();

                if ($category !== null) {
                    $products->whereHas(
                        'type',
                        static fn (Builder $types): Builder => $types->whereIn(
                            'category_id',
                            $categoryIds,
                        ),
                    );
                }
            })
            ->orderBy('name_en')
            ->get();
    }

    /**
     * Active categories that have at least one active product, for the
     * category filter on the search results page.
     *
     * @return Collection<int, Category>
     */
    public function categoryOptions(): Collection
    {
        return Category::query()
            ->active()
            ->whereHas(
                'products',
                static fn (Builder $products): Builder => $products->active(),
            )
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->get();
    }

    /**
     * The filterable specs of the types in scope, each with the control and the
     * values its `filter_type` asks for.
     *
     * A spec name is declared once per type ("Storage" on every phone type)
     * but the storefront shows it once, so the definitions are folded by name.
     * The values come from one batched, cached resolution for the whole scope.
     *
     * @return SupportCollection<int, SpecFilterData>
     */
    public function specOptions(?Category $category = null): SupportCollection
    {
        $locale = app()->getLocale();

        $categoryIds = $category === null
            ? []
            : $this->categoryTreeService->descendantIds($category);

        $specs = TypeSpec::query()
            ->where('is_filterable', true)
            ->whereNotNull('filter_type')
            ->whereHas('type', static function (Builder $types) use ($categoryIds): void {
                if ($categoryIds !== []) {
                    $types->whereIn(
                        'category_id',
                        $categoryIds,
                    );
                }
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->unique(static fn (TypeSpec $spec): string => $spec->nameFor($locale))
            ->values();

        $values = $this->specValuesService->for(
            $specs,
            $this->baseQuery($category),
            $category,
            $locale,
        );

        return $specs->map(static fn (TypeSpec $spec): SpecFilterData => new SpecFilterData(
            id: (int) $spec->getKey(),
            name: $spec->nameFor($locale),
            filterType: $spec->filter_type,
            valueType: $spec->value_type,
            unit: $spec->unit,
            values: $spec->usesRangeFilter()
                ? []
                : $values[(int) $spec->getKey()] ?? [],
        ));
    }
}
