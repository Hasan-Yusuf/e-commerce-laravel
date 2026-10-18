<x-layouts::app
    :title="$search !== '' ? __('catalog.search.title') : __('catalog.products.index_title')"
    :description="__('catalog.products.meta_description')"
>
    @pushOnce('styles')
        @vite([
            'resources/css/features/catalog.css',
            'resources/css/features/filters.css',
            'resources/css/features/search.css',
        ])
    @endPushOnce

    <section class="content-section">
        {{-- The heading is the page's own, at the page's measure. --}}
        <div class="wrapper">
            <header class="search-header">
                <h1 class="page-header__title">
                    {{ $search !== '' ? __('catalog.search.title') : __('catalog.products.index_title') }}
                </h1>

                @if ($search === '')
                    <p class="search-header__hint">{{ __('catalog.search.hint') }}</p>
                @endif
            </header>
        </div>

        {{-- The listing is a column of filters beside a grid of products, and
             both are worth the room when the screen has it. --}}
        <div class="wrapper wrapper--wide">
            {{-- The search, the filters, the count and the products are one
                 Livewire component, so a change answers itself where it is
                 made. --}}
            <livewire:catalog-browser />
        </div>
    </section>
</x-layouts::app>
