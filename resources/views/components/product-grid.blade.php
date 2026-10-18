@props([
    'products',
    'empty' => null,
    'emptyTitle' => null,
    'emptyAction' => null,
    'emptyHref' => null,
    // The level the cards' titles are headings at, which is the level the page
    // they are drawn on calls for.
    'headingLevel' => 3,
    // How many of the first cards are the ones the page is waiting on: those are
    // fetched with the document instead of as the grid is scrolled to.
    'priorityCount' => 0,
])

@if ($products->isEmpty())
    <x-empty-state
        :title="$emptyTitle ?? __('catalog.empty.title')"
        :message="$empty ?? __('catalog.empty.products')"
        :action="$emptyAction"
        :href="$emptyHref"
    />
@else
    <div class="products-grid">
        @foreach ($products as $product)
            {{-- Keyed so a listing that re-renders in place — a filter, a sort —
                 moves the cards it keeps rather than repainting each position. --}}
            <x-product-card
                :data="$product"
                :heading-level="$headingLevel"
                :priority="$loop->index < $priorityCount"
                wire:key="product-{{ $product->product->id }}"
            />
        @endforeach
    </div>
@endif
