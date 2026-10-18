<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\DTOs\CartItemResult;
use App\DTOs\CartLineData;
use App\DTOs\CartSummary;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * The storefront cart: resolving the right cart (guest session vs. user),
 * stock-validated mutations whose results store a price snapshot, totals, and
 * the guest → user merge used when a shopper logs in.
 *
 * The behaviour lives in focused collaborators — resolution, mutation,
 * revalidation, merge and line assembly — which this facade wires together and
 * exposes to the controllers, Livewire components and views. Moving a line
 * between the cart and the wishlist belongs to neither list, so it lives in
 * CartWishlistTransferer instead.
 */
class CartService
{
    public const int GUEST_LIFETIME_DAYS = CartResolver::GUEST_LIFETIME_DAYS;

    public const int USER_LIFETIME_DAYS = CartResolver::USER_LIFETIME_DAYS;

    public function __construct(
        private CartLineAssembler $cartLineAssembler,
        private CartResolver $cartResolver,
        private CartMutator $cartMutator,
        private CartRevalidator $cartRevalidator,
        private CartMerger $cartMerger,
    ) {}

    /**
     * The current cart's items, cached for the request so the summary and the
     * quantity bindings read the same rows rather than querying them twice.
     *
     * @var Collection<int, CartItem>|null
     */
    private ?Collection $cachedItems = null;

    /**
     * The cart the cached items belong to, so a cart swap (login merge) never
     * serves another cart's lines.
     */
    private ?int $cachedCartId = null;

    public function resolve(): Cart
    {
        return $this->cartResolver->current();
    }

    public function findCart(): ?Cart
    {
        return $this->cartResolver->find();
    }

    /**
     * The current cart's items with their products eager loaded. Lines whose
     * product was soft-deleted resolve to a null product.
     *
     * @return Collection<int, CartItem>
     */
    public function cartItems(): Collection
    {
        $cart = $this->cartResolver->find();

        if ($cart === null) {
            return new Collection;
        }

        $cartId = (int) $cart->getKey();

        if ($this->cachedItems === null || $this->cachedCartId !== $cartId) {
            $this->cachedItems = $cart->cartItems()->with('product.media')->get();
            $this->cachedCartId = $cartId;
        }

        return $this->cachedItems;
    }

    /**
     * Drop the cached items so the next read is fresh. Every mutation calls
     * this, so a render that follows one reads the lines just written.
     */
    public function forgetCartItems(): void
    {
        $this->cachedItems = null;
        $this->cachedCartId = null;
    }

    /**
     * @return \Illuminate\Support\Collection<int, CartLineData>
     */
    public function cartLines(): \Illuminate\Support\Collection
    {
        return $this->cartLineAssembler->fromItems($this->cartItems());
    }

    /**
     * The lines plus the totals shown on the cart page, resolving the cart once.
     */
    public function cartSummary(): CartSummary
    {
        $lines = $this->cartLines();

        return new CartSummary(
            lines: $lines,
            itemCount: (int) $lines->sum('quantity'),
            subtotalInFils: (int) $lines->sum(
                static fn (CartLineData $line): int => $line->lineTotalInFils(),
            ),
        );
    }

    public function subtotalInFils(): int
    {
        return $this->cartSummary()->subtotalInFils;
    }

    /**
     * The total quantity across every line (used for the header badge).
     */
    public function itemCount(): int
    {
        $cart = $this->cartResolver->find();

        return $cart === null ? 0 : (int) $cart->cartItems()->sum('quantity');
    }

    public function isEmpty(): bool
    {
        return $this->cartItems()->isEmpty();
    }

    public function add(Product $product, int $quantity): CartItemResult
    {
        $result = $this->cartMutator->add($product, $quantity);

        $this->forgetCartItems();

        return $result;
    }

    public function update(CartItem $item, int $quantity): CartItemResult
    {
        $result = $this->cartMutator->update($item, $quantity);

        $this->forgetCartItems();

        return $result;
    }

    public function remove(CartItem $item): void
    {
        $this->cartMutator->remove($item);

        $this->forgetCartItems();
    }

    /**
     * Empty the current cart without deleting it.
     */
    public function clear(): void
    {
        $this->cartMutator->clear();

        $this->forgetCartItems();
    }

    public function revalidate(): void
    {
        $this->cartRevalidator->revalidate();

        $this->forgetCartItems();
    }

    public function merge(User $user, ?string $sessionId = null): void
    {
        $this->cartMerger->merge($user, $sessionId);

        $this->forgetCartItems();
    }

    public function pruneExpired(): void
    {
        $this->cartResolver->pruneExpired();
    }
}
