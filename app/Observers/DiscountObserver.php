<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Discount;
use App\Services\Catalog\ProductDiscountResolver;

class DiscountObserver
{
    /**
     * A discount was added, changed, or activated, so any pricing already
     * resolved from the old set is stale.
     */
    public function saved(Discount $discount): void
    {
        $this->forgetApplicableDiscounts();
    }

    /**
     * A discount was removed, so a product that resolved it must not keep
     * pricing from it.
     */
    public function deleted(Discount $discount): void
    {
        $this->forgetApplicableDiscounts();
    }

    private function forgetApplicableDiscounts(): void
    {
        app(ProductDiscountResolver::class)->flush();
    }
}
