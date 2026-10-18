<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Product;

/**
 * The products already read during this request.
 *
 * A product card's controls (add to cart, wishlist) live in a component of
 * their own, and each used to read its product back out of the database: a
 * listing of twelve cards cost twelve extra queries, and a listing that keeps
 * appending as it is scrolled paid that again with every batch.
 *
 * A listing hands over the set it has just read, so those reads are the same
 * instances rather than fresh queries. A product nobody handed over is read
 * once and remembered for the rest of the request.
 *
 * Registered as a singleton, like the cart and the wishlist: the point is that
 * everything rendered in one request shares it.
 */
class LoadedProducts
{
    /**
     * Keyed by product id. A null is a product that was asked for and is not
     * there, remembered so it is not asked for twice.
     *
     * @var array<int, Product|null>
     */
    private array $products = [];

    /**
     * @param  iterable<int, Product>  $products
     */
    public function remember(iterable $products): void
    {
        foreach ($products as $product) {
            $this->products[(int) $product->getKey()] = $product;
        }
    }

    public function find(int $productId): ?Product
    {
        if (! array_key_exists($productId, $this->products)) {
            $this->products[$productId] = Product::query()->find($productId);
        }

        return $this->products[$productId];
    }
}
