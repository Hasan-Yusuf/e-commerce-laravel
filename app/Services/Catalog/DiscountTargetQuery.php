<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Turns products into the morph targets a discount can hang off, and loads the
 * discount ids attached to those targets — batched so a listing resolves many
 * products in a constant number of queries.
 *
 * Kept apart from ProductDiscountResolver, which owns the caching and the
 * active-window filter, so neither has to carry the other's control flow.
 */
class DiscountTargetQuery
{
    /**
     * The discount ids attached to each product's targets, keyed by product id.
     *
     * @param  iterable<int, Product>  $products
     * @return array<int, array<int, int>>
     */
    public function candidateIdsFor(iterable $products): array
    {
        $products = Collection::make($products)
            ->filter(static fn (mixed $product): bool => $product instanceof Product)
            ->values();

        if ($products->isEmpty()) {
            return [];
        }

        $categoryIdsByType = $this->categoryIdsByType($products);

        $targetsByProduct = $products
            ->mapWithKeys(
                fn (Product $product): array => [
                    (int) $product->getKey() => $this->targetsFor(
                        $product,
                        $this->categoryIdFor($product, $categoryIdsByType),
                    ),
                ],
            )
            ->all();

        $idsByTarget = $this->discountIdsByTarget(
            $this->uniqueTargets($targetsByProduct),
        );

        return array_map(
            static fn (array $targets): array => self::idsForTargets(
                $targets,
                $idsByTarget,
            ),
            $targetsByProduct,
        );
    }

    /**
     * The product's category id. An eager-loaded type answers without a query;
     * otherwise the id comes from the batched lookup the caller already ran.
     *
     * @param  array<int, int>  $categoryIdsByType
     */
    private function categoryIdFor(Product $product, array $categoryIdsByType): ?int
    {
        if ($product->relationLoaded('type')) {
            /** @var Type|null $type */
            $type = $product->getRelation('type');

            return self::toIntOrNull($type?->category_id);
        }

        return $categoryIdsByType[(int) $product->type_id] ?? null;
    }

    /**
     * The discount targets a product resolves through: itself, its type, its
     * category (when known), and its brand (when it has one).
     *
     * @return array<int, array{0: string, 1: int}>
     */
    private function targetsFor(Product $product, ?int $categoryId): array
    {
        $targets = [
            [$product->getMorphClass(), (int) $product->getKey()],
            [(new Type)->getMorphClass(), (int) $product->type_id],
        ];

        if ($categoryId !== null) {
            $targets[] = [(new Category)->getMorphClass(), $categoryId];
        }

        $brandId = $product->brand_id;

        if ($brandId !== null) {
            $targets[] = [(new Brand)->getMorphClass(), (int) $brandId];
        }

        return $targets;
    }

    /**
     * The category each of the collection's types belongs to, in one query so
     * a bulk pricing pass does not lazy-load a type per product.
     *
     * @param  Collection<int, Product>  $products
     * @return array<int, int>
     */
    private function categoryIdsByType(Collection $products): array
    {
        $typeIds = $products
            ->reject(
                static fn (Product $product): bool => $product->relationLoaded('type'),
            )
            ->pluck('type_id')
            ->filter()
            ->unique()
            ->values();

        if ($typeIds->isEmpty()) {
            return [];
        }

        /** @var array<int, int> $categoryIds */
        return Type::query()
            ->whereKey($typeIds)
            ->pluck('category_id', 'id')
            ->map(static fn (mixed $categoryId): int => (int) $categoryId)
            ->all();
    }

    /**
     * The distinct targets across every product, so the one query below covers
     * a whole listing.
     *
     * @param  array<int, array<int, array{0: string, 1: int}>>  $targetsByProduct
     * @return array<int, array{0: string, 1: int}>
     */
    private function uniqueTargets(array $targetsByProduct): array
    {
        $targets = [];

        foreach ($targetsByProduct as $productTargets) {
            foreach ($productTargets as $target) {
                $targets[self::key($target)] = $target;
            }
        }

        return array_values($targets);
    }

    /**
     * The discount ids attached to each of the given targets, in one query.
     *
     * @param  array<int, array{0: string, 1: int}>  $targets
     * @return array<string, array<int, int>>
     */
    private function discountIdsByTarget(array $targets): array
    {
        if ($targets === []) {
            return [];
        }

        $rows = DB::table('discountables')
            ->select(['discount_id', 'discountable_type', 'discountable_id'])
            ->distinct()
            ->where(static function ($query) use ($targets) {
                foreach ($targets as [$morphType, $morphId]) {
                    $query->orWhere(
                        static function ($builder) use ($morphType, $morphId) {
                            $builder->where('discountable_type', $morphType)
                                ->where('discountable_id', $morphId);
                        },
                    );
                }
            })
            ->get();

        return $rows
            ->groupBy(
                static fn (stdClass $row): string => self::key([
                    (string) $row->discountable_type,
                    (int) $row->discountable_id,
                ]),
            )
            ->map(
                static fn (Collection $group): array => $group
                    ->pluck('discount_id')
                    ->map(static fn (mixed $id): int => (int) $id)
                    ->all(),
            )
            ->all();
    }

    /**
     * The distinct discount ids the given targets carry.
     *
     * @param  array<int, array{0: string, 1: int}>  $targets
     * @param  array<string, array<int, int>>  $idsByTarget
     * @return array<int, int>
     */
    private static function idsForTargets(array $targets, array $idsByTarget): array
    {
        return collect($targets)
            ->flatMap(
                static fn (array $target): array => $idsByTarget[self::key($target)] ?? [],
            )
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array{0: string, 1: int}  $target
     */
    private static function key(array $target): string
    {
        return $target[0].'|'.$target[1];
    }

    private static function toIntOrNull(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
