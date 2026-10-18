<x-layouts::app :title="$category->name" :description="$category->description ?: __('meta.default_description')">
    @pushOnce('styles')
        @vite([
            'resources/css/features/catalog.css',
            'resources/css/features/filters.css',
            'resources/css/features/search.css',
        ])
    @endPushOnce

    <section class="content-section">
        {{-- The category's own heading and its way down into it, at the page's
             measure. --}}
        <div class="wrapper">
            <header class="catalog-header page-header page-header--spaced">
                <h1 class="page-header__title">{{ $category->name }}</h1>

                @if ($category->description)
                    <p class="catalog-header__description">{{ $category->description }}</p>
                @endif

                <p class="page-header__subtitle">{{ trans_choice('catalog.categories.count', $totalProducts) }}</p>
            </header>

            @if ($category->children->isNotEmpty())
                <nav class="subcategories" aria-label="{{ __('catalog.categories.subcategories') }}">
                    <h2 class="subcategories__title">{{ __('catalog.categories.subcategories') }}</h2>

                    <ul class="subcategories__list">
                        @foreach ($category->children as $child)
                            <li>
                                <x-button variant="secondary" size="sm" :href="route('category.show', $child->slug)">
                                    {{ $child->name }}
                                </x-button>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </div>

        {{-- The listing is a column of filters beside a grid of products, and
             both are worth the room when the screen has it. --}}
        <div class="wrapper wrapper--wide">
            {{-- One Livewire component, told only which category it is inside:
                 its filters are its own state, so what is ticked is answered in
                 place and the address bar keeps up. --}}
            <livewire:catalog-browser :category-id="$category->id" />
        </div>
    </section>
</x-layouts::app>
