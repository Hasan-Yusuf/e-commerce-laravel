<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\DTOs\ProductCardData;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductService
{
    /**
     * How many cards one homepage rail holds. A rail scrolls sideways, so a
     * section can offer more than the handful a wrapped grid could show without
     * growing.
     */
    public const int PER_SECTION = 10;

    /**
     * How many cards each product-page rail holds.
     */
    public const int RELATED_COUNT = 4;

    public function __construct(
        private ProductQueryService $productQuery,
        private ProductCardAssembler $cardAssembler,
    ) {}

    /**
     * Every product rail the homepage offers, keyed by the variable the view
     * reads each one as. Each is a section under its own name — Top Picks, Mega
     * Deals, Just Landed — because a rail a shopper can name is a rail they can
     * decide about; the keys are those names in camel case.
     *
     * The keys are the view's own names, so the controller spreads the array
     * into the view data and the view reads `$topPicks`, `$megaDeals` and
     * `$justLanded` exactly as if each had been passed on its own.
     *
     * @return array{topPicks: Collection<int, ProductCardData>, megaDeals: Collection<int, ProductCardData>, justLanded: Collection<int, ProductCardData>}
     */
    public function getHomePageSections(int $count = self::PER_SECTION): array
    {
        return [
            'topPicks' => $this->getFeaturedProducts($count),
            'megaDeals' => $this->getOnSaleProducts($count),
            'justLanded' => $this->getNewlyArrivedProducts($count),
        ];
    }

    /**
     * The featured products, newest feature first — the "Top Picks" rail.
     *
     * @return Collection<int, ProductCardData>
     */
    public function getFeaturedProducts(int $count = self::PER_SECTION): Collection
    {
        return $this->cards(
            $this->productQuery->baseQuery()->featured()->latest('featured_at'),
            $count,
        );
    }

    /**
     * The products carrying an active discount, newest first — the "Mega Deals"
     * rail.
     *
     * @return Collection<int, ProductCardData>
     */
    public function getOnSaleProducts(int $count = self::PER_SECTION): Collection
    {
        return $this->cards(
            $this->productQuery->baseQuery()->discounted()->latest(),
            $count,
        );
    }

    /**
     * The newest products in the catalogue — the "Just Landed" rail.
     *
     * @return Collection<int, ProductCardData>
     */
    public function getNewlyArrivedProducts(
        int $count = self::PER_SECTION,
    ): Collection {
        return $this->cards(
            $this->productQuery->baseQuery()->latest(),
            $count,
        );
    }

    public function restockProduct(Product $product, int $restockAmount): bool
    {
        return $product->increment('stock', $restockAmount) > 0;
    }

    /**
     * The other products of the same type — the "related" rail on a product
     * page. The product itself is excluded; the query is the same active,
     * card-ready one every other listing starts from.
     *
     * @return Collection<int, ProductCardData>
     */
    public function relatedFor(
        Product $product,
        int $count = self::RELATED_COUNT,
    ): Collection {
        return $this->cards(
            $this->productQuery
                ->baseQuery()
                ->where('type_id', $product->type_id)
                ->whereKeyNot($product->getKey()),
            $count,
        );
    }

    /**
     * Other featured products — the "recommended" rail on a product page,
     * newest feature first.
     *
     * @return Collection<int, ProductCardData>
     */
    public function recommendedFor(
        Product $product,
        int $count = self::RELATED_COUNT,
    ): Collection {
        return $this->cards(
            $this->productQuery
                ->baseQuery()
                ->featured()
                ->whereKeyNot($product->getKey())
                ->latest('featured_at'),
            $count,
        );
    }

    /**
     * One rail's worth of cards: the products the query gathers, carrying the
     * media their cards show and priced once for the whole rail.
     *
     * The query comes from `ProductQueryService::baseQuery()`, so a rail is
     * narrowed the same way every other listing is: active products, only the
     * media their cards read.
     *
     * @param  Builder<Product>  $query
     * @return Collection<int, ProductCardData>
     */
    private function cards(Builder $query, int $count): Collection
    {
        return $this->cardAssembler->fromProducts(
            $query->limit($count)->get(),
        );
    }
}
