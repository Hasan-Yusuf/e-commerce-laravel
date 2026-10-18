<?php

use App\DTOs\FlashMessage;
use App\Models\Product;
use App\Models\WishlistItem;
use App\Services\Cart\CartMessages;
use App\Services\Cart\CartService;
use App\Services\Catalog\LoadedProducts;
use App\Services\Wishlist\WishlistMessages;
use App\Services\Wishlist\WishlistService;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The add-to-cart and wishlist controls for a single product.
 *
 * They live in a Livewire component so a click updates this product's buttons
 * (and the header cart badge) in place: the plain forms these replaced posted
 * and redirected, which reloaded the page and threw the shopper back to the top.
 * Every form keeps a real action, so the same clicks still work as a normal
 * POST when JavaScript is unavailable.
 */
new class extends Component
{
    public int $productId;

    /** 'card' = product card, 'detail' = product page (with a quantity). */
    public string $variant = 'card';

    public int|string $quantity = 1;

    public function mount(int $productId, string $variant = 'card'): void
    {
        $this->productId = $productId;
        $this->variant = $variant;
    }

    #[Computed]
    public function product(): ?Product
    {
        // The listing this card sits in has already read the product, and hands
        // it over for the request; only a card read on its own (a click on this
        // one card, or the product page) reads it here.
        return app(LoadedProducts::class)->find($this->productId);
    }

    /**
     * The saved entry for this product, or null when it is not wishlisted.
     */
    #[Computed]
    public function wishlistItem(): ?WishlistItem
    {
        $product = $this->product;

        return $product === null
            ? null
            : app(WishlistService::class)->findItemForProduct($product);
    }

    #[Computed]
    public function availableStock(): int
    {
        $product = $this->product;

        return $product === null ? 0 : max(0, $product->getAvailableStock());
    }

    #[Computed]
    public function isPurchasable(): bool
    {
        $product = $this->product;

        return $product !== null && $product->isActive() && $this->availableStock > 0;
    }

    public function addToCart(): void
    {
        $product = $this->product;

        if ($product === null) {
            return;
        }

        $this->validate(
            ['quantity' => ['required', 'integer', 'min:1', 'max:999']],
            ['quantity' => __('catalog.product.quantity')],
        );

        $result = app(CartService::class)->add($product, (int) $this->quantity);

        $this->notify(CartMessages::forAdded($result, $product));
        $this->dispatch('cart-updated');
    }

    /**
     * Save the product, or drop it again when it is already saved — the same
     * button covers both, so the heart simply flips.
     */
    public function toggleWishlist(): void
    {
        $product = $this->product;

        if ($product === null) {
            return;
        }

        $wishlist = app(WishlistService::class);
        $item = $this->wishlistItem;

        if ($item !== null) {
            $wishlist->remove($item);

            $this->notify(WishlistMessages::removed());
        } else {
            $result = $wishlist->add($product);

            $this->notify(
                $result->alreadyExists
                    ? WishlistMessages::exists($product)
                    : WishlistMessages::added($product),
            );
        }

        // `wishlistItem` is a computed property, cached for the rest of this
        // request (and read above to pick the branch), so drop it to let this
        // render show the state we just wrote.
        unset($this->wishlistItem);
    }

    /**
     * The type is dispatched as the word the toast's own class is named after, so
     * the browser receives it plain rather than as an enum.
     */
    private function notify(FlashMessage $message): void
    {
        $this->dispatch(
            'notify',
            ...$message->toArray(),
        );
    }
};
?>

@php
    $product = $this->product;
    $wishlistItem = $this->wishlistItem;
@endphp

{{-- One Livewire component owns the controls of one product; `detail` stacks a
     quantity on top of them. The detail variant generates no box of its own so
     its children stay grid items of `.product-detail__grid`. --}}
<div @class([
    'product-card__actions' => $variant === 'card',
    'product-detail__actions-group' => $variant === 'detail',
])>
    @if ($product)
        <form
            @class([
                'product-detail__form' => $variant === 'detail',
            ])
            method="POST"
            action="{{ route('cart.add') }}"
            wire:submit="addToCart"
        >
            @csrf
            <x-inputs.hidden name="product_id" :value="$product->id" />

            @if ($variant === 'detail')
                @if ($this->availableStock > 0)
                    <div class="product-detail__quantity">
                        <label
                            class="product-detail__quantity-label"
                            for="quantity"
                        >{{ __('catalog.product.quantity') }}</label>

                        <x-quantity-selector id="quantity" :max="$this->availableStock" wire-model="quantity" />

                        @error('quantity')
                            <p class="product-detail__error">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div class="product-detail__actions">
                    <x-button type="submit" :disabled="! $this->isPurchasable">
                        {{ __('homepage.add_to_cart') }}
                    </x-button>
                </div>
            @else
                <x-inputs.hidden name="quantity" value="1" />

                <x-button block type="submit" :disabled="! $this->isPurchasable">
                    {{ __('homepage.add_to_cart') }}
                </x-button>
            @endif
        </form>

        @if ($variant === 'detail')
            <div class="product-detail__wishlist">
                <x-wishlist-button
                    :product="$product"
                    :item="$wishlistItem"
                    variant="labeled"
                    wire-submit="toggleWishlist"
                />
            </div>
        @else
            <x-wishlist-button :product="$product" :item="$wishlistItem" wire-submit="toggleWishlist" />
        @endif
    @endif
</div>
