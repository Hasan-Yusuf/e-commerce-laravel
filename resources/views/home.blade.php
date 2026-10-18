<x-layouts::app title="{{ __('homepage.title') }}">
    {{-- The page's own heading. The homepage leads with the hero, which is a
         picture rather than a heading, so the document's outline opens with the
         shop's name kept for assistive technology and search engines. --}}
    <h1 class="visually-hidden">{{ __('homepage.title') }}</h1>

    {{-- Hero banner slider --}}
    <x-banner-slider :banners="$banners" />

    {{-- Categories rail --}}
    <x-categories-section :categories="$categories" :href="route('category.index')" icon="icons.squares-icon" />

    {{-- A product section with nothing in it is not a section, so each one is
         left out rather than rendered empty. Each carries a mark of its own, so
         the three read as three different offers rather than one list repeated. --}}

    {{-- Top Picks: the products the shop put forward --}}
    @if ($topPicks->isNotEmpty())
        <x-products-section
            :products="$topPicks"
            :title="__('homepage.top_picks.title')"
            :href="route('products.index')"
            icon="icons.star-icon"
        />
    @endif

    {{-- Mega Deals: the same cards, narrowed to what carries a discount --}}
    @if ($megaDeals->isNotEmpty())
        <x-products-section
            :products="$megaDeals"
            :title="__('homepage.mega_deals.title')"
            :href="route('products.index', ['discounted' => 1])"
            icon="icons.percent-icon"
        />
    @endif

    {{-- Just Landed: the newest products in the catalogue --}}
    @if ($justLanded->isNotEmpty())
        <x-products-section
            :products="$justLanded"
            :title="__('homepage.just_landed.title')"
            :href="route('products.index', ['sort' => 'newest'])"
            icon="icons.sparkles-icon"
        />
    @endif
</x-layouts::app>
