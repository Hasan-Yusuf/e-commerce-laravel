<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\DTOs\CartLineData;
use App\Models\CartItem;
use App\Services\Catalog\ProductPricingService;
use App\Services\ImageService;
use Illuminate\Support\Collection;

/**
 * Builds the display data for a cart line. The assembly lives here rather than
 * on the DTO itself, so CartLineData stays plain data: the pricing and the
 * thumbnail are handed in rather than reached for out of the container.
 */
final class CartLineAssembler
{
    public function __construct(
        private ProductPricingService $pricingService,
        private ImageService $imageService,
    ) {}

    /**
     * The lines for a whole cart, warming the pricing cache once so no line's
     * DTO queries the discounts on its own.
     *
     * @param  iterable<int, CartItem>  $items
     * @return Collection<int, CartLineData>
     */
    public function fromItems(iterable $items): Collection
    {
        $items = Collection::make($items);

        $this->pricingService->getDiscountsFor($items->pluck('product')->filter());

        return $items
            ->map($this->fromItem(...))
            ->values();
    }

    public function fromItem(CartItem $item): CartLineData
    {
        $product = $item->product;

        $currentPriceInFils = 0;
        $availableStock = 0;

        if ($product !== null) {
            $currentPriceInFils = $this->pricingService->getCurrentPrice($product);
            $availableStock = max(0, $product->getAvailableStock());
        }

        return new CartLineData(
            item: $item,
            product: $product,
            quantity: (int) $item->quantity,
            snapshotPriceInFils: $item->price_in_fils->toFils(),
            currentPriceInFils: $currentPriceInFils,
            availableStock: $availableStock,
            thumbnail: $this->imageService->forMedia($product?->getFirstMedia('thumbnail')),
        );
    }
}
