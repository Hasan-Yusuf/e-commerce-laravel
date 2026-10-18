@props([
    'products',
    'empty' => null,
    'emptyTitle' => null,
    'emptyAction' => null,
    'emptyHref' => null,
    // How many of the first rows are the ones the page is waiting on: those are
    // fetched with the document instead of as the list is scrolled to.
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
    <div class="product-list">
        @foreach ($products as $product)
            {{-- Keyed as the grid is: the rows a re-render keeps stay put. --}}
            <x-product-row
                :data="$product"
                :priority="$loop->index < $priorityCount"
                wire:key="product-{{ $product->product->id }}"
            />
        @endforeach
    </div>
@endif
