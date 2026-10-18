<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\DTOs\ProductCardData;
use App\Models\Product;
use App\Services\ImageService;
use App\Support\Money;
use Closure;
use Illuminate\Support\Collection;

/**
 * Builds the card data a listing shows. The assembly lives here rather than on
 * the DTO itself, so ProductCardData stays plain data: the pricing, the
 * thumbnail and the request-scoped product lookup are handed in rather than
 * reached for out of the container.
 */
final class ProductCardAssembler
{
    public function __construct(
        private ProductPricingService $pricingService,
        private ImageService $imageService,
        private LoadedProducts $loadedProducts,
    ) {}

    public function fromProduct(Product $product): ProductCardData
    {
        $brand = $product->brand;

        return new ProductCardData(
            product: $product,
            currentPrice: Money::fromFils($this->pricingService->getCurrentPrice($product)),
            originalPrice: $product->price_in_fils,
            discount: $this->pricingService->getDiscount($product),
            thumbnail: $this->imageService->forMedia($product->getFirstMedia('thumbnail')),
            brandName: $brand?->name,
        );
    }

    /**
     * Map a collection of products to card data, warming the discounts and the
     * request-scoped lookup once for the whole collection so no card runs a
     * query of its own.
     *
     * @param  iterable<int, Product>  $products
     * @return Collection<int, ProductCardData>
     */
    public function fromProducts(iterable $products): Collection
    {
        $products = Collection::make($products);

        $this->remember($products);

        return $products->map($this->fromProduct(...));
    }

    /**
     * Warm the pricing cache and hand the products to the request-scoped lookup
     * without building the cards, for a caller that maps them itself.
     *
     * @param  iterable<int, Product>  $products
     */
    public function remember(iterable $products): void
    {
        $products = Collection::make($products);

        $this->pricingService->getDiscountsFor($products);
        $this->loadedProducts->remember($products);
    }

    /**
     * A single-argument mapper, handy for paginator through() calls:
     * `->through($assembler->mapper())`.
     *
     * @return Closure(Product): ProductCardData
     */
    public function mapper(): Closure
    {
        return $this->fromProduct(...);
    }
}
