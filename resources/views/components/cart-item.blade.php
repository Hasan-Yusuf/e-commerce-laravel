@props([
    'line',
])

@php
    $item = $line->item;
    $itemId = $item->id;
    $product = $line->product;
    $formatPrice = \App\Support\Money::fromFils(...);

    $isEditable = $product !== null && ! $line->isUnavailable();

    // Collected up front so the wrapper is only rendered when there is something
    // to say about the line — an empty block would still hold a grid row open.
    $notices = [];

    if ($product === null) {
        $notices[] = __('carts.item.unavailable_product');
    } elseif ($line->isInactive()) {
        $notices[] = __('carts.item.inactive_product');
    } elseif ($line->isUnavailable()) {
        $notices[] = __('carts.item.out_of_stock');
    } elseif ($line->exceedsStock()) {
        $notices[] = __('carts.item.exceeds_stock', [
            'count' => $line->availableStock,
        ]);
    }

    if ($product && $line->priceChanged()) {
        $notices[] = __('carts.item.price_changed', [
            'old' => $formatPrice($line->snapshotPriceInFils),
            'new' => $formatPrice($line->currentPriceInFils),
        ]);
    }
@endphp

{{-- One cart line. The list it sits in owns the column tracks, and this is a
     subgrid of them wherever the list is wide enough (see cart.css), so the
     thumbnail, the details, the stepper, the total and the controls line up from
     line to line. --}}
<article class="cart-item" wire:key="cart-item-{{ $itemId }}">
    <div class="cart-item__media">
        @if ($product && $line->thumbnail)
            <a href="{{ route('products.show', $product->slug) }}">
                <x-media-image :image="$line->thumbnail" :alt="$product->name" sizes="80px" />
            </a>
        @endif
    </div>

    <div class="cart-item__info">
        @if ($product)
            <a class="cart-item__name" href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
            <x-stock-badge :product="$product" />
        @else
            <span class="cart-item__name"> {{ __('carts.item.unavailable_product') }} </span>
        @endif

        <p class="cart-item__unit-price">
            <span class="visually-hidden">{{ __('carts.item.unit_price') }}:</span>
            {{ $formatPrice($line->currentPriceInFils) }}
        </p>

        @if ($notices !== [])
            <div class="cart-item__notices">
                @foreach ($notices as $notice)
                    <x-alert type="warning" :message="$notice" />
                @endforeach
            </div>
        @endif
    </div>

    <div class="cart-item__quantity">
        @if ($isEditable)
            {{-- The debounced model owns the update, so block the native
                 submit with JS; without JS the form still posts normally. --}}
            <form
                class="cart-item__quantity-form"
                method="POST"
                action="{{ route('cart.update', $item) }}"
                x-on:submit.prevent
            >
                @csrf
                @method('PATCH')

                <div
                    class="quantity-selector"
                    x-data="{
                        step(direction) {
                            const input = $refs.quantity;

                            direction > 0 ? input.stepUp() : input.stepDown();
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        },
                    }"
                >
                    <button
                        type="button"
                        class="quantity-selector__button js-only"
                        x-on:click="step(-1)"
                        wire:loading.attr="disabled"
                        aria-label="{{ __('catalog.product.decrease_quantity') }}"
                    >
                        &minus;
                    </button>

                    <input
                        x-ref="quantity"
                        class="quantity-selector__input"
                        type="number"
                        name="quantity"
                        min="1"
                        max="{{ $line->availableStock }}"
                        value="{{ $line->quantity }}"
                        wire:model.live.debounce.750ms="quantities.{{ $itemId }}"
                        wire:loading.attr="disabled"
                        aria-label="{{ __('carts.item.quantity') }}"
                    />

                    <button
                        type="button"
                        class="quantity-selector__button js-only"
                        x-on:click="step(1)"
                        wire:loading.attr="disabled"
                        aria-label="{{ __('catalog.product.increase_quantity') }}"
                    >
                        &plus;
                    </button>
                </div>

                <button type="submit" class="cart-item__update no-js-only">{{ __('carts.actions.update') }}</button>
            </form>

            @error('quantity.'.$itemId)
                <p class="cart-item__error">{{ $message }}</p>
            @enderror
        @else
            <span class="cart-item__quantity-value"> {{ __('carts.item.quantity') }}: {{ $line->quantity }} </span>
        @endif
    </div>

    <div class="cart-item__total">
        <span class="cart-item__total-label | visually-hidden"> {{ __('carts.item.line_total') }} </span>
        <span class="cart-item__total-value"> {{ $formatPrice($line->lineTotalInFils()) }} </span>
    </div>

    <div class="cart-item__actions">
        <form
            class="cart-item__remove-form"
            method="POST"
            action="{{ route('cart.remove', $item) }}"
            wire:submit.debounce.500ms="removeItem({{ $itemId }})"
        >
            @csrf
            @method('DELETE')

            <x-button
                variant="danger"
                size="icon"
                type="submit"
                wire:loading.attr="disabled"
                :aria-label="__('carts.actions.remove')"
                :title="__('carts.actions.remove')"
            >
                <x-icons.trash-icon aria-hidden="true" />
            </x-button>
        </form>

        <form
            class="cart-item__move-form"
            method="POST"
            action="{{ route('cart.moveToWishlist', $item) }}"
            wire:submit.debounce.500ms="moveToWishlist({{ $itemId }})"
        >
            @csrf

            <x-button
                variant="tertiary"
                size="icon"
                type="submit"
                :disabled="! $isEditable"
                wire:loading.attr="disabled"
                :aria-label="__('carts.actions.move_to_wishlist')"
                :title="__('carts.actions.move_to_wishlist')"
            >
                <x-icons.heart-icon aria-hidden="true" />
            </x-button>
        </form>
    </div>
</article>
