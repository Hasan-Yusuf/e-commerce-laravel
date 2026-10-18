<?php

use App\DTOs\CartSummary;
use App\DTOs\FlashMessage;
use App\DTOs\WishlistLineData;
use App\Models\CartItem;
use App\Models\WishlistItem;
use App\Services\Cart\CartMessages;
use App\Services\Cart\CartService;
use App\Services\Cart\CartWishlistTransferer;
use App\Services\Wishlist\WishlistMessages;
use App\Services\Wishlist\WishlistService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The interactive cart, with the wishlist sitting directly under it: quantity
 * edits, removals and the moves between the two lists all happen over Livewire
 * so both lists and the order summary stay in sync without a full page reload.
 * Quantity inputs are debounced, so a run of keystrokes or stepper clicks
 * settles on one saved value instead of one request per change. Each mutation
 * dispatches `cart-updated` (refreshes the header badge) and `notify`.
 */
new class extends Component {
    /**
     * The quantity shown in each line's input, keyed by item id. Bindings write
     * here on a debounce; `updatedQuantities` then persists the settled value.
     *
     * @var array<int, int>
     */
    public array $quantities = [];

    public function mount(): void
    {
        $this->syncQuantities();
    }

    /**
     * Persist a debounced change to a line's bound quantity.
     */
    public function updatedQuantities(
        mixed $value,
        ?string $itemId = null,
    ): void {
        if ($itemId === null) {
            return;
        }

        $this->updateQuantity((int) $itemId, $value);
    }

    /**
     * Persist the quantity typed into a line's input. Reached from the debounced
     * model binding and from the no-JS form submit fallback.
     */
    public function updateQuantity(int $itemId, mixed $value): void
    {
        $key = 'quantity.'.$itemId;

        Validator::make(
            ['quantity' => [$itemId => $value]],
            [
                $key => ['required', 'integer', 'min:1', 'max:999'],
            ],
        )->validate();

        $this->applyQuantity($itemId, (int) $value);
    }

    public function removeItem(int $itemId): void
    {
        $item = $this->findItem($itemId);

        if ($item === null) {
            return;
        }

        app(CartService::class)->remove($item);

        unset($this->quantities[$itemId]);

        $this->notify(CartMessages::removed());
        $this->dispatch('cart-updated');
    }

    /**
     * Move a line out of the cart and onto the wishlist. The line is only
     * dropped when its product actually made it onto the list, so a product
     * that left the catalogue is not lost with the cart row.
     */
    public function moveToWishlist(int $itemId): void
    {
        $item = $this->findItem($itemId);

        if ($item === null) {
            return;
        }

        $product = $item->product;
        $result = app(CartWishlistTransferer::class)->moveToWishlist($item);

        if ($result->unavailable) {
            $this->notify(WishlistMessages::moveFromCartFailed());

            return;
        }

        unset($this->quantities[$itemId]);

        $this->notify(
            $result->alreadyExists
                ? WishlistMessages::alreadyWishlisted($product)
                : WishlistMessages::movedFromCart($product),
        );
        $this->dispatch('cart-updated');
    }

    /**
     * Move a saved entry into the cart. The entry is only dropped when its
     * product reached the cart, so an unavailable product stays saved.
     */
    public function moveToCart(int $itemId): void
    {
        $item = $this->findWishlistItem($itemId);

        if ($item === null) {
            return;
        }

        $product = $item->product;
        $result = app(CartWishlistTransferer::class)->moveToCart($item);

        if ($result->unavailable || $product === null) {
            $this->notify(WishlistMessages::moveToCartUnavailable($product));

            return;
        }

        $quantity = (int) ($result->item?->quantity ?? 1);

        $this->notify(
            $result->clamped
                ? WishlistMessages::movedToCartClamped($product, $quantity)
                : WishlistMessages::movedToCart($product, $quantity),
        );
        $this->dispatch('cart-updated');
    }

    /**
     * Drop a saved entry without touching the cart.
     */
    public function removeFromWishlist(int $itemId): void
    {
        $item = $this->findWishlistItem($itemId);

        if ($item === null) {
            return;
        }

        app(WishlistService::class)->remove($item);

        $this->notify(WishlistMessages::removed());
    }

    /**
     * Move every line onto the wishlist at once. Lines whose product left the
     * catalogue stay in the cart, so the toast reports what actually moved
     * instead of announcing a clean sweep.
     */
    public function moveAllToWishlist(): void
    {
        $result = app(CartWishlistTransferer::class)->moveAllToWishlist();

        if ($result->isEmpty()) {
            return;
        }

        $this->notify(WishlistMessages::movedAllFromCart($result));

        // Nothing moved means nothing in the cart changed, so the bound inputs
        // and the header badge are left alone.
        if ($result->moved > 0) {
            $this->syncQuantities();
            $this->dispatch('cart-updated');
        }
    }

    /**
     * Move every saved entry into the cart at once. Entries whose product
     * cannot be bought right now stay saved.
     */
    public function moveAllToCart(): void
    {
        $result = app(CartWishlistTransferer::class)->moveAllToCart();

        if ($result->isEmpty()) {
            return;
        }

        $this->notify(WishlistMessages::movedAllToCart($result));

        if ($result->moved > 0) {
            $this->syncQuantities();
            $this->dispatch('cart-updated');
        }
    }

    /**
     * The lines plus totals, recomputed on every render so the summary reacts to
     * quantity changes and removals.
     */
    #[Computed]
    public function summary(): CartSummary
    {
        return app(CartService::class)->cartSummary();
    }

    /**
     * The saved entries shown under the cart, recomputed on every render so a
     * move in either direction is reflected without a reload.
     *
     * @return Collection<int, WishlistLineData>
     */
    #[Computed]
    public function wishlist(): Collection
    {
        return app(WishlistService::class)->lines();
    }

    /**
     * Re-read the quantity inputs from the cart, so a change that touches many
     * lines at once (a bulk move) cannot leave a binding pointing at a line
     * that is gone.
     */
    private function syncQuantities(): void
    {
        $this->quantities = app(CartService::class)
            ->cartItems()
            ->pluck('quantity', 'id')
            ->map(fn (int|string $quantity): int => (int) $quantity)
            ->all();
    }

    private function applyQuantity(int $itemId, int $quantity): void
    {
        $item = $this->findItem($itemId);

        if ($item === null) {
            return;
        }

        $product = $item->product;
        $result = app(CartService::class)->update($item, $quantity);
        $applied = (int) ($result->item?->quantity ?? $quantity);

        // Mirror what was actually saved (the service may clamp to stock) so the
        // bound input cannot resend a stale value on the next debounce.
        $this->quantities[$itemId] = $applied;

        $messages = match (true) {
            $result->unavailable => CartMessages::unavailable($product),
            $result->clamped => CartMessages::clamped($product, $applied),
            default => CartMessages::updated($applied),
        };

        $this->notify($messages);
        $this->dispatch('cart-updated');
    }

    /**
     * Only lines belonging to the current cart are addressable, so a tampered id
     * resolves to null instead of another shopper's line.
     */
    private function findItem(int $itemId): ?CartItem
    {
        return app(CartService::class)->cartItems()->firstWhere('id', $itemId);
    }

    /**
     * Same guard for the wishlist: only the current shopper's saved entries are
     * addressable.
     */
    private function findWishlistItem(int $itemId): ?WishlistItem
    {
        return app(WishlistService::class)->items()->firstWhere('id', $itemId);
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

<div class="cart-page">
    <header class="cart-header wrapper">
        <div class="page-header__heading">
            <h1 class="page-header__title">{{ __('carts.title') }}</h1>

            @unless ($this->summary->isEmpty())
                <p class="page-header__subtitle">{{ trans_choice('carts.count', $this->summary->itemCount) }}</p>
            @endunless
        </div>

        @unless ($this->summary->isEmpty())
            {{-- A real form, so the whole-cart move also works without JS. --}}
            <form
                class="cart-header__action"
                method="POST"
                action="{{ route('cart.moveAllToWishlist') }}"
                wire:submit.debounce.500ms="moveAllToWishlist"
            >
                @csrf

                <x-button variant="secondary" type="submit" wire:loading.attr="disabled">
                    {{ __('carts.actions.move_all_to_wishlist') }}
                </x-button>
            </form>
        @endunless
    </header>

    @if ($this->summary->isEmpty())
        <div class="wrapper">
            <x-empty-state
                icon="cart"
                :title="__('carts.empty.title')"
                :message="__('carts.empty.message')"
                :action="__('carts.empty.action')"
                :href="route('products.index')"
            />
        </div>
    @else
        {{-- The line items and the order summary want the wide measure; the
             rest of the page keeps the normal one. --}}
        <div class="cart-layout wrapper wrapper--wide">
            <div class="cart-lines">
                <div class="cart-items">
                    {{-- Column names, shown only where the lines lay out as columns.
                         The cells name themselves for screen readers, so this row
                         is decorative. --}}
                    <div class="cart-items__header" aria-hidden="true">
                        <span class="cart-items__header-cell"> {{ __('carts.item.image') }} </span>
                        <span class="cart-items__header-cell"> {{ __('carts.item.product') }} </span>
                        <span class="cart-items__header-cell"> {{ __('carts.item.unit_price') }} </span>
                        <span class="cart-items__header-cell"> {{ __('carts.item.quantity') }} </span>
                        <span class="cart-items__header-cell"> {{ __('carts.item.line_total') }} </span>
                        <span class="cart-items__header-cell"> {{ __('carts.item.action') }} </span>
                    </div>

                    @foreach ($this->summary->lines as $line)
                        <x-cart-item :line="$line" />
                    @endforeach
                </div>
            </div>

            <x-cart-summary :summary="$this->summary" />
        </div>
    @endif

    {{-- The wishlist sits under the cart, so entries can be moved between the
         two lists without leaving the page. --}}
    <section id="wishlist" class="wishlist-section wrapper">
        <header class="wishlist-section__header">
            <div class="page-header__heading">
                <h2 class="page-header__title">{{ __('wishlist.title') }}</h2>

                @unless ($this->wishlist->isEmpty())
                    <p class="page-header__subtitle">
                        {{ trans_choice('wishlist.count', $this->wishlist->count()) }}
                    </p>
                @endunless
            </div>

            @unless ($this->wishlist->isEmpty())
                {{-- A real form, so moving the whole list into the cart also
                     works without JS. --}}
                <form
                    class="wishlist-section__action"
                    method="POST"
                    action="{{ route('wishlist.moveAllToCart') }}"
                    wire:submit.debounce.500ms="moveAllToCart"
                >
                    @csrf

                    <x-button variant="secondary" type="submit" wire:loading.attr="disabled">
                        {{ __('wishlist.actions.move_all_to_cart') }}
                    </x-button>
                </form>
            @endunless
        </header>

        @if ($this->wishlist->isEmpty())
            <p class="wishlist-section__empty">{{ __('wishlist.empty.message') }}</p>
        @else
            <div class="products-grid">
                @foreach ($this->wishlist as $line)
                    @php
                        $wishlistItem = $line->item;
                        $wishlistItemId = $wishlistItem->id;
                        $product = $line->product;
                    @endphp

                    <article
                        class="product-card wishlist-card @unless ($product) wishlist-card--gone @endunless"
                        wire:key="wishlist-card-{{ $wishlistItemId }}"
                    >
                        @if ($product)
                            <div class="product-card__badges">
                                <x-stock-badge :product="$product" />
                            </div>

                            <a class="product-card__link" href="{{ route('products.show', $product->slug) }}">
                                <div class="product-card__image">
                                    @if ($image = $line->card->thumbnail)
                                        <img src="{{ $image->src }}" alt="{{ $product->name }}" loading="lazy" />
                                    @endif
                                </div>

                                <h3 class="product-card__title">{{ $product->name }}</h3>
                            </a>

                            <div class="product-card__meta">
                                <x-product-price
                                    :current-price="$line->card->currentPrice"
                                    :original-price="$line->card->originalPrice"
                                    :discount="$line->card->discount"
                                />
                            </div>
                        @else
                            <x-alert type="warning" :message="__('wishlist.item.unavailable_product')" />
                        @endif

                        <div class="wishlist-card__actions">
                            @if ($product)
                                {{-- The primary action is the move into the cart. --}}
                                <form
                                    class="wishlist-card__move-form"
                                    method="POST"
                                    action="{{ route('wishlist.moveToCart', $wishlistItem) }}"
                                    wire:submit.debounce.500ms="moveToCart({{ $wishlistItemId }})"
                                >
                                    @csrf

                                    <x-button
                                        block
                                        type="submit"
                                        :disabled="! $product->isActive() || $product->getAvailableStock() <= 0"
                                    >
                                        {{ __('wishlist.actions.move_to_cart') }}
                                    </x-button>
                                </form>
                            @endif

                            <form
                                class="wishlist-card__remove-form"
                                method="POST"
                                action="{{ route('wishlist.remove', $wishlistItem) }}"
                                wire:submit.debounce.500ms="removeFromWishlist({{ $wishlistItemId }})"
                            >
                                @csrf
                                @method('DELETE')

                                <x-button
                                    variant="danger"
                                    size="icon"
                                    type="submit"
                                    wire:loading.attr="disabled"
                                    :aria-label="__('wishlist.actions.remove')"
                                    :title="__('wishlist.actions.remove')"
                                >
                                    <x-icons.trash-icon aria-hidden="true" />
                                </x-button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
