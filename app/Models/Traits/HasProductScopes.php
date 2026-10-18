<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait HasProductScopes
{
    /**
     * What is actually sellable: on-hand stock less what open carts and orders
     * have already reserved. Written once so every stock scope and
     * Product::getAvailableStock() answer the same question the same way.
     */
    public const string AVAILABLE_STOCK_EXPRESSION = 'stock - reserved_stock';

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('activated_at', '<=', now());
    }

    #[Scope]
    protected function featured(Builder $query): void
    {
        $query->where('featured_at', '<=', now());
    }

    /**
     * Products with an active discount attached to the product, its type,
     * its category, or its brand.
     */
    #[Scope]
    protected function discounted(Builder $query): void
    {
        $activeDiscounts = static fn (
            Builder $discounts,
        ): Builder => $discounts->currentlyActive();

        $query->where(
            static fn (Builder $products): Builder => $products
                ->whereHas('discounts', $activeDiscounts)
                ->orWhereHas('type.discounts', $activeDiscounts)
                ->orWhereHas('category.discounts', $activeDiscounts)
                ->orWhereHas('brand.discounts', $activeDiscounts),
        );
    }

    #[Scope]
    protected function withAvailableStock(Builder $query): void
    {
        $query->select()->selectExpression(self::AVAILABLE_STOCK_EXPRESSION, 'available_stock');
    }

    /**
     * The sellable quantity is zero or less.
     */
    #[Scope]
    protected function outOfStock(Builder $query): void
    {
        $query->whereRaw(self::AVAILABLE_STOCK_EXPRESSION.' <= 0');
    }

    /**
     * Sellable quantity is above zero but at or below the restock threshold.
     */
    #[Scope]
    protected function lowStock(Builder $query): void
    {
        $query
            ->whereRaw(self::AVAILABLE_STOCK_EXPRESSION.' > 0')
            ->whereRaw(self::AVAILABLE_STOCK_EXPRESSION.' <= low_stock_threshold');
    }

    /**
     * Sellable quantity is above the restock threshold.
     */
    #[Scope]
    protected function inStock(Builder $query): void
    {
        $query->whereRaw(self::AVAILABLE_STOCK_EXPRESSION.' > low_stock_threshold');
    }

    /**
     * Products `isLowOnStock()` answers true for: sellable quantity at or below
     * the threshold, which is what the low-stock navigation badge counts.
     */
    #[Scope]
    protected function lowOnStock(Builder $query): void
    {
        $query->whereRaw(self::AVAILABLE_STOCK_EXPRESSION.' <= low_stock_threshold');
    }
}
