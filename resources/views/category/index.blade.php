<x-layouts::app :title="__('homepage.categories.title')">
    @pushOnce('styles')
        @vite('resources/css/features/catalog.css')
    @endPushOnce

    <section class="content-section">
        <div class="wrapper">
            <header class="catalog-header page-header page-header--spaced">
                <h1 class="page-header__title">{{ __('homepage.categories.title') }}</h1>
            </header>

            @forelse ($sections as $section)
                <section class="category-section">
                    <x-section-header
                        :title="$section->category->name"
                        :href="route('category.show', $section->category->slug)"
                        :label="__('catalog.categories.see_all')"
                        size="sm"
                    />

                    {{-- The children read as a way down from the heading above
                         them, so they follow it as a row of chips of their own
                         rather than squeezing into the header beside it. --}}
                    @if ($section->category->children->isNotEmpty())
                        <nav class="subcategories" aria-label="{{ __('catalog.categories.subcategories') }}">
                            <ul class="subcategories__list">
                                @foreach ($section->category->children as $child)
                                    <li>
                                        <x-button
                                            variant="secondary"
                                            size="sm"
                                            :href="route('category.show', $child->slug)"
                                        >{{ $child->name }}</x-button>
                                    </li>
                                @endforeach
                            </ul>
                        </nav>
                    @endif

                    @if ($section->products->isNotEmpty())
                        <x-product-grid :products="$section->products" />
                    @endif
                </section>
            @empty
                <x-empty-state
                    icon="info"
                    :title="__('catalog.empty.title')"
                    :message="__('homepage.categories.empty')"
                />
            @endforelse

            {{ $sections->links('pagination::bootstrap-5') }}
        </div>
    </section>
</x-layouts::app>
