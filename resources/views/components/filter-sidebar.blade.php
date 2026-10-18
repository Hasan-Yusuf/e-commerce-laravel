@props([
    // Where the filters are posted when there is no JavaScript to answer them as
    // they change: the listing they narrow, handed in by the component.
    // `url()->current()` cannot stand in for it — on a Livewire re-render the
    // current URL is the update endpoint, which a browser cannot GET.
    'action',
    'preserve' => [],
])

<div class="filter-panel" data-filter-panel>
    <x-button
        variant="secondary"
        class="filter-panel__toggle"
        data-filter-toggle
        aria-controls="catalog-filters"
        aria-expanded="false"
        {{-- Open and shut by the shopper, not by the server: left alone so a
             filter answered behind the panel does not tell them it is shut. --}}
        wire:ignore.self
    >
        {{ __('catalog.filters.open') }}
    </x-button>

    {{-- The sidebar is a form so its filters can be posted without JavaScript;
         with it, every control answers the listing as it changes and the form is
         never submitted. Its own state rides along as hidden fields, so the sort
         and the view survive a posted filter. --}}
    <aside class="filter-sidebar" id="catalog-filters" data-filter-sidebar data-open="false" wire:ignore.self>
        <div class="filter-sidebar__header">
            <span class="filter-sidebar__title"> {{ __('catalog.filters.title') }} </span>

            <x-button
                variant="bare"
                class="filter-sidebar__close"
                data-filter-close
                :aria-label="__('catalog.filters.close')"
            >
                <x-icons.close-icon aria-hidden="true" />
            </x-button>
        </div>

        <form class="filter-form form" method="GET" action="{{ $action }}" wire:submit="apply">
            <x-inputs.hidden-params :params="$preserve" />

            {{ $slot }}

            <div class="filter-form__actions">
                {{-- With JavaScript every control has already answered, so the
                     submit is only the way a form without it is posted. --}}
                <x-button class="filter-form__apply no-js-only" block type="submit">
                    {{ __('catalog.filters.apply') }}
                </x-button>

                <x-button variant="ghost" block :href="$action" wire:click.prevent="clear">
                    {{ __('catalog.filters.clear') }}
                </x-button>
            </div>
        </form>
    </aside>

    {{-- The panel's own covering: what is under it is the shopper's to click
         away on. Left alone for the same reason as the panel itself, so a
         filter answered behind it does not take the covering away. --}}
    <div class="filter-sidebar__overlay" data-filter-overlay wire:ignore.self hidden></div>
</div>
