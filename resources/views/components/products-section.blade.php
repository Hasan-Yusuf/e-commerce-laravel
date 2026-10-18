@props([
    'products' => collect(),
    'title' => null,
    'href' => null,
    'icon' => null,
])

<section class="content-section content-section--products">
    <div class="wrapper">
        <x-section-header :title="$title ?? __('homepage.top_picks.title')" :href="$href" :icon="$icon" />
    </div>

    {{-- The row and its way along. The scroller spans the page while the row
         keeps the page's measure, so the buttons stand in the gutter beside the
         cards, as the hero's do, rather than over the cards' edges. --}}
    <div class="scroller" data-scroller-group>
        <x-scroller-nav />

        <div class="wrapper">
            <div class="products-grid products-grid--rail" data-scroller>
                @foreach ($products as $product)
                    <x-product-card :data="$product" />
                @endforeach
            </div>
        </div>
    </div>
</section>
