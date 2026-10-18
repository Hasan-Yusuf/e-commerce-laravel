@props([
    'product',
    'item' => null,
    'variant' => 'icon',
    'wireSubmit' => null,
])

@php
    // Resolved through the request-scoped service, so a card does not have to
    // be handed the entry it is saving; the list is read once per request.
    $item ??= app(\App\Services\Wishlist\WishlistService::class)
        ->findItemForProduct($product);

    $isWishlisted = $item !== null;

    $label = $isWishlisted
        ? __('wishlist.actions.remove')
        : __('wishlist.actions.add');

    $buttonClass = implode(' ', array_filter([
        'wishlist-button',
        $variant === 'labeled' ? 'wishlist-button--labeled' : null,
        $isWishlisted ? 'wishlist-button--active' : null,
    ]));
@endphp

<form
    class="wishlist-button__form"
    method="POST"
    action="{{ $isWishlisted ? route('wishlist.remove', $item) : route('wishlist.add') }}"
    @if ($wireSubmit) wire:submit="{{ $wireSubmit }}" @endif
>
    @csrf

    @if ($isWishlisted)
        @method('DELETE')
    @else
        <x-inputs.hidden name="product_id" :value="$product->id" />
    @endif

    <x-button
        :variant="$isWishlisted ? 'danger' : 'tertiary'"
        :size="$variant === 'icon' ? 'icon' : null"
        type="submit"
        :class="$buttonClass"
        :title="$label"
        :aria-pressed="$isWishlisted ? 'true' : 'false'"
    >
        <x-icons.heart-icon class="wishlist-button__icon" aria-hidden="true" />

        @if ($variant === 'icon')
            <span class="visually-hidden">{{ $label }}</span>
        @else
            <span class="wishlist-button__label">{{ $label }}</span>
        @endif
    </x-button>
</form>
