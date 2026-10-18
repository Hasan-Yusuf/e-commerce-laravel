@props([
    // Where the form posts without JavaScript: the listing's own URL, handed in
    // by the component. `url()->current()` cannot be used for it — on a Livewire
    // re-render the current URL is the update endpoint, which the browser cannot
    // GET, so an implicit submit (Enter on the select) would 405.
    'action',
    'current' => \App\Services\Catalog\ProductQueryService::DEFAULT_SORT,
    'preserve' => [],
    // The Livewire property the sort answers, when the listing is live: the
    // change is then answered in place, and the form below is only the way a
    // browser without JavaScript posts the same choice.
    'wireModel' => null,
])

<form class="catalog-sort" method="GET" action="{{ $action }}" x-on:submit.prevent>
    <x-inputs.hidden-params :params="$preserve" />

    <label class="catalog-sort__label" for="catalog-sort"> {{ __('catalog.sort.label') }} </label>

    <select
        class="catalog-sort__select"
        id="catalog-sort"
        name="sort"
        @if ($wireModel)
            wire:model.live="{{ $wireModel }}"
        @else
            {{-- Nothing but a script submits the form on a change, so the
                 attribute rides along only where the script is what answers it. --}}
            data-auto-submit
        @endif
    >
        @foreach (\App\Services\Catalog\ProductQueryService::SORTS as $key => $definition)
            <option value="{{ $key }}" @selected($current === $key)>{{ __('catalog.sort.'.$key) }}</option>
        @endforeach
    </select>

    <x-button variant="bare" class="visually-hidden" type="submit"> {{ __('catalog.sort.apply') }} </x-button>
</form>
