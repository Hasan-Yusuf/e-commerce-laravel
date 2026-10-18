@props([
    // Where the form posts without JavaScript: the listing's own URL, handed in
    // by the component. `url()->current()` cannot be used for it — on a Livewire
    // re-render the current URL is the update endpoint, which the browser cannot
    // GET, so an implicit submit would 405.
    'action',
    'current' => 'grid',
    'preserve' => [],
    // The Livewire property the choice answers, when the listing is live: the
    // switch is then made in place, and the form below is only the way a browser
    // without JavaScript posts the same choice.
    'wireModel' => null,
])

{{-- Switches the product listing between the grid and the cart-style list. --}}
<form class="catalog-view" method="GET" action="{{ $action }}" x-on:submit.prevent>
    <x-inputs.hidden-params :params="$preserve" />

    <span class="catalog-view__label" id="catalog-view-label"> {{ __('catalog.view.label') }} </span>

    <div class="catalog-view__options" role="radiogroup" aria-labelledby="catalog-view-label">
        @foreach (['grid', 'list'] as $view)
            <label class="catalog-view__option" title="{{ __('catalog.view.'.$view) }}">
                <input
                    class="catalog-view__input visually-hidden"
                    type="radio"
                    name="view"
                    value="{{ $view }}"
                    @if ($wireModel)
                        wire:model.live="{{ $wireModel }}"
                    @else
                        {{-- As in the sort control: only the script submits. --}}
                        data-auto-submit
                    @endif
                    @checked($current === $view)
                />

                @if ($view === 'grid')
                    <x-icons.grid-icon class="catalog-view__icon" aria-hidden="true" />
                @else
                    <x-icons.list-icon class="catalog-view__icon" aria-hidden="true" />
                @endif

                <span class="visually-hidden">{{ __('catalog.view.'.$view) }}</span>
            </label>
        @endforeach
    </div>

    <x-button variant="bare" class="visually-hidden" type="submit"> {{ __('catalog.view.apply') }} </x-button>
</form>
