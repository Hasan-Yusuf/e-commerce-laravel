@props([
    'data',
    // The level the card's title is a heading at. A listing the page titles with
    // one heading puts its products directly under it; a rail that sits under a
    // section heading puts them a level deeper.
    'headingLevel' => 3,
    // Whether this card is one of the first the page shows, and so one the page
    // is waiting on rather than fetching as it is scrolled to.
    'priority' => false,
])

@php
    $product = $data->product;
@endphp

<article class="product-card" {{ $attributes }}>
    <div class="product-card__badges">
        <x-stock-badge :product="$product" />
    </div>

    <a class="product-card__link" href="{{ route('products.show', $product->slug) }}">
        <div class="product-card__image">
            {{-- The card is one column of the grid it is in: a fixed column once
                 the grid has room for more than one, and the width of the page
                 while it has room for only one. --}}
            <x-media-image
                :image="$data->thumbnail"
                :alt="$product->name"
                sizes="(min-width: 600px) 300px, calc(100vw - 32px)"
                :priority="$priority"
            />
        </div>

        <div class="product-card__heading">
            {{-- The name names the product above its title, quietly. The card
                 shows the name rather than the logo, so every card reads the
                 same whatever the shop uploaded. --}}
            @if ($data->brandName)
                <p class="product-card__brand">{{ $data->brandName }}</p>
            @endif

            <h{{ $headingLevel }} class="product-card__title">{{ $product->name }}</h{{ $headingLevel }}>
        </div>
    </a>

    <div class="product-card__meta">
        <x-product-price
            :current-price="$data->currentPrice"
            :original-price="$data->originalPrice"
            :discount="$data->discount"
        />
    </div>

    {{-- Add to cart + wishlist toggle: one Livewire component per card, so a
         click updates this card and the header badge without a page reload. --}}
    <livewire:product-actions :product-id="$product->id" :key="'product-actions-'.$product->id" />
</article>
