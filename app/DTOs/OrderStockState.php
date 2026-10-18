<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * The stock a pipeline run is holding and writing: the cart's products, locked
 * for the transaction, and the products whose stock this run touches.
 */
final readonly class OrderStockState
{
    /**
     * @param  Collection<int, Product>  $lockedProducts  the cart's products, locked for this transaction
     * @param  Collection<int, int>  $affectedProductIds  the products whose stock this run writes
     */
    public function __construct(
        public Collection $lockedProducts = new Collection,
        public Collection $affectedProductIds = new Collection,
    ) {}

    /**
     * @param  Collection<int, Product>  $lockedProducts
     */
    public function withLockedProducts(Collection $lockedProducts): self
    {
        return new self($lockedProducts, $this->affectedProductIds);
    }

    /**
     * @param  Collection<int, int>  $affectedProductIds
     */
    public function withAffectedProductIds(Collection $affectedProductIds): self
    {
        return new self($this->lockedProducts, $affectedProductIds);
    }
}
