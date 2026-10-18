@props([
    'product',
    // The product's images, the thumbnail first, each already resolved by
    // App\Services\ImageService.
    'images' => collect(),
])

<div class="product-gallery" data-product-gallery>
    <div class="product-gallery__main">
        @if ($images->isNotEmpty())
            <x-media-image
                :image="$images->first()"
                :alt="$product->name"
                data-gallery-main
                sizes="(min-width: 900px) 55vw, calc(100vw - 32px)"
                priority
            />
        @else
            <div class="product-gallery__placeholder">
                <x-icons.info-icon class="product-gallery__placeholder-icon" aria-hidden="true" />
                <span>{{ __('catalog.product.gallery_empty') }}</span>
            </div>
        @endif
    </div>

    @if ($images->count() > 1)
        <ul class="product-gallery__thumbs">
            @foreach ($images as $image)
                <li>
                    <a
                        class="product-gallery__thumb"
                        href="{{ $image->src }}"
                        data-gallery-thumb
                        data-src="{{ $image->src }}"
                        aria-current="{{ $loop->first ? 'true' : 'false' }}"
                    >
                        <x-media-image :image="$image" :alt="$product->name" sizes="72px" />
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
