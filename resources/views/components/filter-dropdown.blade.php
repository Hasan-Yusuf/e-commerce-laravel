@props([
    // The group's title, as the other filter groups carry it.
    'title',
    // What the choices are called on the wire: `brand[]`, `specs[3][]`.
    'name',
    // Stored value => displayed label, in the order they are shown.
    'options' => [],
    // The values that came back selected.
    'selected' => [],
    // The Livewire property the choices answer, when the listing is live.
    'wireModel' => null,
])

@php
    // A filter carries its selection back as strings (ids, labels, "1"), and a
    // single-value request arrives unwrapped, so both are normalized here.
    $selected = array_map('strval', (array) $selected);

    $chosen = array_values(array_intersect_key($options, array_flip($selected)));

    // A summary is a line, not a paragraph: it names at most this many choices
    // and counts the rest. The script that keeps the summary in step reads that
    // number, and the wording below, off the control rather than repeating them.
    $maxLabels = 3;

    $summary = match (true) {
        $chosen === [] => __('catalog.filters.choose'),
        count($chosen) <= $maxLabels => implode(', ', $chosen),
        default => implode(', ', array_slice($chosen, 0, $maxLabels)).' '
            .__('catalog.filters.more', [
                'count' => count($chosen) - $maxLabels,
            ]),
    };
@endphp

{{-- A list of choices is a dropdown rather than a stack of checkboxes: the
     catalogue has more brands, categories and spec values than a sidebar can
     show, and the box stays one line tall whatever is in it. The choices are
     still checkboxes, so several can be picked at once. --}}
<x-filter-group :title="$title">
    <details
        class="filter-dropdown"
        data-filter-dropdown
        {{-- Opened by the shopper, not by the server: left alone so answering a
             choice does not shut the list they are still choosing from. --}}
        wire:ignore.self
        {{-- The word the summary falls back to when nothing is chosen: the
             script that keeps the summary in step reads it from here, so the
             phrasing stays the catalogue's rather than the script's. --}}
        data-empty-label="{{ __('catalog.filters.choose') }}"
        data-max-labels="{{ $maxLabels }}"
        data-more-label="{{ __('catalog.filters.more', ['count' => ':count']) }}"
    >
        <summary class="filter-dropdown__summary">
            <span class="filter-dropdown__value">{{ $summary }}</span>

            <x-icons.chevron-down-icon size="16" class="filter-dropdown__chevron" aria-hidden="true" />
        </summary>

        <div class="filter-dropdown__options">
            @foreach ($options as $value => $label)
                <x-inputs.checkbox
                    :name="$name"
                    :value="$value"
                    :wire-model="$wireModel"
                    :label="$label"
                    wrapper-class="filter-checkbox"
                    :checked="in_array((string) $value, $selected, true)"
                />
            @endforeach
        </div>
    </details>
</x-filter-group>
