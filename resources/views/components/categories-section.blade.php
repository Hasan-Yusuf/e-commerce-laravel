@props([
    'categories' => collect(),
    'title' => null,
    'href' => null,
    'icon' => null,
])

<section class="content-section">
    <div class="wrapper">
        <x-section-header :title="$title ?? __('homepage.categories.title')" :href="$href" :icon="$icon" />
    </div>

    {{-- The row and its way along. The scroller spans the page while the row
         keeps the page's measure, so the buttons stand in the gutter beside the
         cards, as the hero's do, rather than over the cards' edges. --}}
    <div class="scroller" data-scroller-group>
        <x-scroller-nav />

        <div class="wrapper">
            <div class="categories-grid categories-grid--rail" data-scroller>
                @foreach ($categories as $category)
                    @php($image = \App\Services\ImageService::forMedia($category->getFirstMedia('thumbnail')))

                    <x-category-card :category="$category" :image="$image" />
                @endforeach
            </div>
        </div>
    </div>
</section>
