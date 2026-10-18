@props([
    // The applied filters, as built by ActiveFilterChips.
    'filters' => [],
])

{{-- Each chip carries the way out of the filter it stands for twice: the link is
     what a browser without JavaScript follows, and the click is what the listing
     answers in place, without giving up its place in the results. --}}
@if (! empty($filters))
    <div class="active-filters">
        <span class="active-filters__label"> {{ __('catalog.search.active_filters') }} </span>

        <ul class="active-filters__list">
            @foreach ($filters as $filter)
                <li>
                    <a
                        class="active-filter-chip"
                        href="{{ $filter['url'] }}"
                        wire:click.prevent="removeFilter(@js($filter['filter']), @js($filter['value']))"
                        aria-label="{{ __('catalog.search.remove', ['name' => $filter['label']]) }}"
                    >
                        <span>{{ $filter['label'] }}</span>
                        <x-icons.close-icon class="active-filter-chip__icon" aria-hidden="true" />
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
