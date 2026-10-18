<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\DiscountType;
use App\Models\Discount;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;

class ProductPricingService
{
    public function __construct(
        private readonly ProductDiscountResolver $discountResolver,
    ) {}

    /**
     * The currently active discounts that target the product, its type,
     * its category, or its brand.
     *
     * @return Collection<int, Discount>
     */
    public function getDiscounts(Product $product): Collection
    {
        return $this->discountResolver->forProduct($product);
    }

    /**
     * Warm the per-product cache for a whole collection in three queries
     * (types, discountables, discounts), so a listing does not run a query
     * pair for every card.
     *
     * Pair it with as many getDiscounts()/getCurrentPrice() calls as there are
     * products: those then read the cache instead of querying again.
     *
     * @param  iterable<int, Product>  $products
     */
    public function getDiscountsFor(iterable $products): void
    {
        $this->discountResolver->warm($products);
    }

    /**
     * The best applicable discount for the product (lowest resulting price),
     * or null when no discount currently applies.
     */
    public function getDiscount(Product $product): ?Discount
    {
        $priceInFils = $product->price_in_fils->toFils();

        $best = null;
        $bestPrice = null;

        foreach ($this->getDiscounts($product) as $discount) {
            $price = $this->calculatePrice($priceInFils, $discount);

            if ($bestPrice === null || $price < $bestPrice) {
                $best = $discount;
                $bestPrice = $price;
            }
        }

        return $best;
    }

    /**
     * The current unit price in fils, after the best applicable discount.
     */
    public function getCurrentPrice(Product $product): int
    {
        $priceInFils = $product->price_in_fils->toFils();

        $discount = $this->getDiscount($product);

        if ($discount !== null) {
            return $this->calculatePrice($priceInFils, $discount);
        }

        return $priceInFils;
    }

    /**
     * The fils amount saved by the best applicable discount.
     */
    public function getDiscountAmount(Product $product): int
    {
        return $product->price_in_fils->toFils() - $this->getCurrentPrice($product);
    }

    private function calculatePrice(int $priceInFils, Discount $discount): int
    {
        $discountValue = (float) $discount->value;

        $discountAmount = $discount->type->is(DiscountType::PERCENTAGE)
            ? Money::percentage($priceInFils, $discountValue)
            : Money::fromBHD($discountValue)->toFils();

        return max(0, $priceInFils - $discountAmount);
    }
}
