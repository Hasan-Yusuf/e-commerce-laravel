@php
    // What the product tells a search engine about itself: the meta description
    // the admin wrote when there is one, otherwise the opening of the same
    // description a shopper reads on the page.
    $metaDescription = filled($product->meta_description)
        ? $product->meta_description
        : \Illuminate\Support\Str::limit((string) $product->description, 160);
@endphp

<x-layouts::app :title="$product->name" :description="$metaDescription">
    @pushOnce('styles')
        @vite('resources/css/features/product-page.css')
    @endPushOnce

    <div class="product-page wrapper">
        <section class="product-layout">
            <x-product-gallery :product="$product" :images="$gallery" />

            <div class="product-detail">
                {{-- Two-column layout:
                     brand    | available
                     title (spans both columns)
                     price    | quantity
                     cart     | wishlist --}}
                <div class="product-detail__grid">
                    @if ($product->brand)
                        @php
                            $lightLogo = \App\Services\ImageService::forMedia($product->brand->getFirstMedia('logo_light'));

                            $darkLogo = \App\Services\ImageService::forMedia($product->brand->getFirstMedia('logo_dark'));
                        @endphp

                        <p class="product-detail__brand">
                            {{ __('catalog.product.brand') }}:
                            <x-brand-mark
                                :name="$product->brand->name"
                                :light-logo="$lightLogo"
                                :dark-logo="$darkLogo"
                            />
                        </p>
                    @endif

                    <div class="product-detail__stock">
                        <x-stock-badge :product="$product" />
                    </div>

                    <h1 class="product-detail__title">{{ $product->name }}</h1>

                    <div class="product-detail__price">
                        <x-product-price
                            :current-price="$currentPrice"
                            :original-price="$product->price_in_fils"
                            :discount="$discount"
                        />
                    </div>

                    {{-- Quantity + add to cart + wishlist toggle, owned by one
                         Livewire component so a click updates them (and the
                         header badge) in place instead of reloading the page. --}}
                    <livewire:product-actions
                        :product-id="$product->id"
                        variant="detail"
                        :key="'product-actions-'.$product->id"
                    />
                </div>

                <p class="product-detail__sku">
                    {{ __('catalog.product.sku') }}:
                    <span>{{ $product->sku }}</span>
                </p>
            </div>
        </section>

        <section class="product-section">
            <x-section-header :title="__('catalog.product.specs_title')" />
            <x-specs-table :product="$product" />
        </section>

        <section class="product-section">
            <x-section-header :title="__('catalog.product.description_title')" />

            @if ($product->description)
                <p class="product-description">{{ $product->description }}</p>
            @else
                <p class="product-description">{{ __('catalog.product.no_description') }}</p>
            @endif
        </section>

        @if ($related->isNotEmpty())
            <section class="product-section">
                <x-section-header :title="__('catalog.product.related_title')" />
                <x-product-grid :products="$related" />
            </section>
        @endif

        @if ($recommended->isNotEmpty())
            <section class="product-section">
                <x-section-header :title="__('catalog.product.recommended_title')" />
                <x-product-grid :products="$recommended" />
            </section>
        @endif
    </div>
</x-layouts::app>
