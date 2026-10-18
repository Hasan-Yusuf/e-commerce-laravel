@props([
    'name' => 'quantity',
    'value' => 1,
    'min' => 1,
    'max' => null,
    'id' => 'quantity',
    'wireModel' => null,
])

<div class="quantity-selector" data-quantity-selector>
    <x-button
        variant="bare"
        class="quantity-selector__button"
        data-quantity-decrease
        :aria-label="__('catalog.product.decrease_quantity')"
    >
        &minus;
    </x-button>

    <input
        class="quantity-selector__input"
        type="number"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $value }}"
        min="{{ $min }}"
        @if ($max !== null) max="{{ $max }}" @endif
        @if ($wireModel) wire:model="{{ $wireModel }}" @endif
    />

    <x-button
        variant="bare"
        class="quantity-selector__button"
        data-quantity-increase
        :aria-label="__('catalog.product.increase_quantity')"
    >
        +
    </x-button>
</div>
