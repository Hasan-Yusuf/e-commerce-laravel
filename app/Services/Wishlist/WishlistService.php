<?php

declare(strict_types=1);

namespace App\Services\Wishlist;

use App\DTOs\WishlistItemResult;
use App\DTOs\WishlistLineData;
use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use App\Services\Catalog\ProductCardAssembler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;

/**
 * Owns the storefront wishlist: the resolved list (guest session vs. user
 * account), idempotent add/remove, the guest → user merge used when a shopper
 * logs in, and the display data for the list. Moving entries to the cart lives
 * in CartWishlistTransferer, which also owns the cart → wishlist direction.
 */
class WishlistService
{
    /**
     * The current owner's items, cached for the request so the heart toggle on
     * every product card resolves without a query of its own. The cache is keyed
     * by owner, so it can never leak a guest list into a signed-in one.
     *
     * @var Collection<int, WishlistItem>|null
     */
    private ?Collection $cachedItems = null;

    private ?string $cachedOwnerKey = null;

    public function __construct(
        private ProductCardAssembler $cardAssembler,
        private WishlistMerger $wishlistMerger,
    ) {}

    /**
     * The saved items for the current owner, newest first, with the product, its
     * media and the brand its card names eager loaded. Never creates a row; a
     * shopper with nothing saved simply gets an empty collection.
     *
     * @return Collection<int, WishlistItem>
     */
    public function items(): Collection
    {
        $ownerKey = $this->ownerKey();

        if (
            $this->cachedItems === null
            || $this->cachedOwnerKey !== $ownerKey
        ) {
            $this->cachedItems = $this
                ->query()
                ->with(['product.media', 'product.brand'])
                ->latest('id')
                ->get();
            $this->cachedOwnerKey = $ownerKey;
        }

        return $this->cachedItems;
    }

    /**
     * The saved entries with their display data, for the wishlist page.
     *
     * @return \Illuminate\Support\Collection<int, WishlistLineData>
     */
    public function lines(): \Illuminate\Support\Collection
    {
        $items = $this->items();

        // Warm the pricing cache and the request-scoped product lookup for every
        // saved product in one pass so each line's card does not query its own.
        /** @var \Illuminate\Support\Collection<int, Product> $products */
        $products = $items->pluck('product')->filter();

        $this->cardAssembler->remember($products);

        return $items->map(
            function (WishlistItem $item): WishlistLineData {
                $product = $item->product;

                return new WishlistLineData(
                    item: $item,
                    product: $product,
                    card: $product === null
                        ? null
                        : $this->cardAssembler->fromProduct($product),
                );
            },
        )
            ->values();
    }

    public function count(): int
    {
        return $this->items()->count();
    }

    public function isEmpty(): bool
    {
        return $this->items()->isEmpty();
    }

    public function contains(Product $product): bool
    {
        return $this->findItemForProduct($product) !== null;
    }

    /**
     * The saved entry for a product, or null when it is not on the list.
     */
    public function findItemForProduct(Product $product): ?WishlistItem
    {
        /** @var WishlistItem|null $item */
        return $this->items()->firstWhere('product_id', $product->getKey());
    }

    /**
     * Save a product. Adding something already saved is a no-op, so repeated
     * clicks (or two tabs racing) never duplicate an entry.
     */
    public function add(Product $product): WishlistItemResult
    {
        $existing = $this->findItemForProduct($product);

        if ($existing !== null) {
            return WishlistItemResult::exists($existing);
        }

        $attributes = array_merge($this->ownerAttributes(), [
            'product_id' => $product->getKey(),
        ]);

        try {
            $item = WishlistItem::create($attributes);
        } catch (UniqueConstraintViolationException $exception) {
            // A concurrent add won the race; the entry now exists, so this is
            // still a no-op rather than an error.
            $this->forget();

            $existing = $this->findItemForProduct($product);

            if ($existing === null) {
                throw $exception;
            }

            return WishlistItemResult::exists($existing);
        }

        $this->forget();

        return WishlistItemResult::added($item);
    }

    /**
     * Drop an entry. It must belong to the current list; anything else is a 404.
     */
    public function remove(WishlistItem $item): void
    {
        $this->assertOwned($item);

        $item->delete();

        $this->forget();
    }

    /**
     * Fold the guest (session) list into the given user's list, dropping the
     * guest copy of anything the account already saved. Used on login.
     */
    public function merge(User $user, ?string $sessionId = null): void
    {
        $this->wishlistMerger->merge($user, $sessionId);

        $this->forget();
    }

    /**
     * An entry must belong to the current list; anything else is a 404. Shared
     * with CartWishlistTransferer, which moves an entry once it is accepted.
     */
    public function assertOwned(WishlistItem $item): void
    {
        $userId = Auth::id();

        $owned = $userId === null
            ? $item->user_id === null && (string) $item->session_id === $this->sessionId()
            : (int) $item->user_id === (int) $userId;

        abort_unless($owned, 404);
    }

    /**
     * The current owner's entries: signed-in shoppers own theirs through the
     * account, guests through the session.
     *
     * @return Builder<WishlistItem>
     */
    private function query(): Builder
    {
        $query = WishlistItem::query();
        $userId = Auth::id();

        if ($userId === null) {
            return $query->whereNull('user_id')
                ->where('session_id', $this->sessionId());
        }

        return $query->where('user_id', $userId);
    }

    /**
     * The columns that identify the owner of a newly saved entry.
     *
     * @return array{user_id: int|string|null, session_id: string|null}
     */
    private function ownerAttributes(): array
    {
        $userId = Auth::id();

        return [
            'user_id' => $userId,
            'session_id' => $userId === null ? $this->sessionId() : null,
        ];
    }

    private function ownerKey(): string
    {
        $userId = Auth::id();

        return $userId === null
            ? 'session:'.$this->sessionId()
            : 'user:'.$userId;
    }

    private function forget(): void
    {
        $this->cachedItems = null;
        $this->cachedOwnerKey = null;
    }

    private function sessionId(): string
    {
        return session()->getId();
    }
}
