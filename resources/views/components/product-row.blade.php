@props([
    'data',
    // Whether this row is one of the first the page shows, and so one the page
    // is waiting on rather than fetching as it is scrolled to.
    'priority' => false,
])

@php
    $product = $data->product;
@endphp

{{-- Cart-style horizontal row: media, details, price, actions. --}}
<article class="product-row" {{ $attributes }}>
    <div class="product-row__media">
        <a href="{{ route('products.show', $product->slug) }}">
            <div class="product-row__image">
                {{-- A square the row always draws at the same size. --}}
                <x-media-image :image="$data->thumbnail" :alt="$product->name" sizes="80px" :priority="$priority" />
            </div>
        </a>
    </div>

    <div class="product-row__info">
        <a class="product-row__title" href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>

        @if ($product->brand || $product->type)
            <p class="product-row__meta">
                @if ($product->brand)
                    <span>{{ $product->brand->name }}</span>
                @endif

                @if ($product->type)
                    <span>{{ $product->type->name }}</span>
                @endif
            </p>
        @endif

        <x-stock-badge :product="$product" />
    </div>

    <div class="product-row__price">
        <x-product-price
            :current-price="$data->currentPrice"
            :original-price="$data->originalPrice"
            :discount="$data->discount"
        />
    </div>

    <div class="product-row__actions">
        <form method="POST" action="{{ route('cart.add') }}">
            @csrf
            <x-inputs.hidden name="product_id" :value="$product->id" />
            <x-inputs.hidden name="quantity" value="1" />

            <x-button type="submit" :disabled="! $product->isActive() || $product->getAvailableStock() <= 0">
                {{ __('homepage.add_to_cart') }}
            </x-button>
        </form>
    </div>
</article>
