@props([
    'category',
    'count' => null,
    'image' => null,
])

<a href="{{ route('category.show', $category->slug) }}" class="category-card">
    <div class="category-card__image">
        {{-- A card is one column of the categories grid or one card of its rail:
             a fixed column once the grid has room for more than one, and the
             share of the page the rail gives it while it scrolls. --}}
        <x-media-image :image="$image" :alt="$category->name" sizes="(min-width: 600px) 200px, 55vw" />
    </div>

    <h3 class="category-card__title">{{ $category->name }}</h3>

    @if ($count !== null)
        <p class="category-card__count">{{ trans_choice('catalog.categories.count', $count) }}</p>
    @endif
</a>
