<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\CacheKeys;
use App\Models\Discount;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves the discounts that target a product, its type, its category, or its
 * brand.
 *
 * Resolved once per request into memory, and across requests under
 * CacheKeys::ApplicableProductDiscounts as bare discount ids — never as
 * serialised models, which a persistent store cannot round-trip back into
 * their classes.
 *
 * Candidates are cached rather than the active subset, so a discount whose
 * window opens or closes while an entry lives is applied or dropped by
 * active() on read. DiscountObserver flushes both caches whenever a discount is
 * saved or removed.
 */
class ProductDiscountResolver
{
    /**
     * The candidate discounts already loaded during this request, keyed by
     * product id.
     *
     * @var array<int, Collection<int, Discount>>
     */
    private array $applicableDiscounts = [];

    public function __construct(
        private readonly DiscountTargetQuery $targetQuery = new DiscountTargetQuery,
    ) {}

    /**
     * The active discounts that target the product, its type, its category, or
     * its brand.
     *
     * @return Collection<int, Discount>
     */
    public function forProduct(Product $product): Collection
    {
        $productId = (int) $product->getKey();

        if ($this->isProductInCache($productId)) {
            return $this->active($this->applicableDiscounts[$productId]);
        }

        $discounts = $this->loadDiscounts($productId, $product);

        $this->applicableDiscounts[$productId] = $discounts;

        return $this->active($discounts);
    }

    public function isProductInCache(mixed $productId): bool
    {
        return array_key_exists($productId, $this->applicableDiscounts);
    }

    /**
     * Drop both caches: the request's loaded candidates and the persistent id
     * map. Called when a discount is saved, removed, or activated so the next
     * read recalculates from the new set. A singleton resolver is bound, so
     * this reaches the instance the pricing services injected.
     */
    public function flush(): void
    {
        $this->applicableDiscounts = [];

        Cache::forget(CacheKeys::ApplicableProductDiscounts);
    }

    /**
     * Warm the per-product cache for a whole collection in three queries
     * (types, discountables, discounts), so a listing does not run a query
     * pair for every card.
     *
     * Pair it with as many forProduct() calls as there are products: those
     * then read the cache instead of querying again.
     *
     * @param  iterable<int, Product>  $products
     */
    public function warm(iterable $products): void
    {
        $idsByProduct = $this->targetQuery->candidateIdsFor($products);

        if ($idsByProduct === []) {
            return;
        }

        $discounts = $this->discountsFor(
            collect($idsByProduct)->flatten()->all(),
        );

        $resolved = [];

        foreach ($idsByProduct as $productId => $ids) {
            $resolved[$productId] = $discounts->only($ids)->values();
        }

        $this->applicableDiscounts = array_replace(
            $this->applicableDiscounts,
            $resolved,
        );

        $this->store(array_map($this->discountIds(...), $resolved));
    }

    /**
     * Load the candidates for a product the request has not seen: from the
     * persistent cache when it holds them, otherwise by resolving and caching
     * them.
     *
     * @return Collection<int, Discount>
     */
    private function loadDiscounts(int $productId, Product $product): Collection
    {
        $discountIds = $this->cached()[$productId] ?? null;

        if ($discountIds !== null) {
            return $this->discountsFor($discountIds);
        }

        $discounts = $this->discountsFor(
            $this->targetQuery->candidateIdsFor([$product])[$productId] ?? [],
        );

        $this->store([$productId => $this->discountIds($discounts)]);

        return $discounts;
    }

    /**
     * Resolve the given discount ids to their models, keyed by id, in one
     * query. Whether each is active right now is decided when it is read, so a
     * cached candidate does not need invalidating when its window passes.
     *
     * @param  array<int, int>  $discountIds
     * @return Collection<int, Discount>
     */
    private function discountsFor(array $discountIds): Collection
    {
        $discountIds = collect($discountIds)
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($discountIds === []) {
            return new Collection;
        }

        return Discount::query()
            ->whereKey($discountIds)
            ->get()
            ->keyBy(static fn (Discount $discount): int => (int) $discount->getKey());
    }

    /**
     * The discount ids the persistent cache holds, as a product id => ids map.
     * Anything that is not an id list is dropped, so a malformed or foreign
     * payload is recomputed rather than used.
     *
     * @return array<int, array<int, int>>
     */
    private function cached(): array
    {
        $cached = Cache::get(CacheKeys::ApplicableProductDiscounts, []);

        return is_array($cached) ? array_filter($cached, is_array(...)) : [];
    }

    /**
     * @param  array<int, array<int, int>>  $discountIdsByProduct
     */
    private function store(array $discountIdsByProduct): void
    {
        if ($discountIdsByProduct === []) {
            return;
        }

        Cache::put(
            CacheKeys::ApplicableProductDiscounts,
            array_replace($this->cached(), $discountIdsByProduct),
            now()->addDay(),
        );
    }

    /**
     * @param  Collection<int, Discount>  $discounts
     * @return array<int, int>
     */
    private function discountIds(Collection $discounts): array
    {
        return $discounts
            ->map(static fn (Discount $discount): int => (int) $discount->getKey())
            ->values()
            ->all();
    }

    /**
     * The candidates that are active right now, evaluated on read so a cached
     * entry tracks a discount's window without being invalidated.
     *
     * @param  Collection<int, Discount>  $discounts
     * @return Collection<int, Discount>
     */
    private function active(Collection $discounts): Collection
    {
        return $discounts
            ->filter(
                static fn (Discount $discount): bool => $discount->isCurrentlyActive(),
            )
            ->values();
    }
}
