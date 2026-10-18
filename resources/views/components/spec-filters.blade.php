@props([
    // The filterable specs of the products being listed.
    'specs',
    // The applied filters, so the controls come back checked and filled.
    'filters' => [],
])

{{-- One control per filterable spec, chosen by the spec's own `filter_type`: a
     list of the values the products carry, a min/max pair, or a yes/no. Each is
     a filter of its own, named after itself, however many types declare it.

     Each control answers the listing it is in over Livewire (`specs.<id>`), and
     keeps the field name a submitted form needs: without JavaScript the sidebar
     is posted and the page comes back filtered. --}}
@foreach ($specs as $spec)
    @continue($spec->isEmpty())

    @php
        $selected = array_map(
            'strval',
            (array) data_get($filters, "specs.{$spec->id}", []),
        );
    @endphp

    @if ($spec->isRange())
        @php
            $minimum = data_get($filters, "specs.{$spec->id}.min");
            $maximum = data_get($filters, "specs.{$spec->id}.max");
        @endphp

        <x-filter-group :title="$spec->title()">
            <div class="filter-price">
                <label class="visually-hidden" for="spec-{{ $spec->id }}-min">
                    {{ __('catalog.filters.spec_min', ['name' => $spec->name]) }}
                </label>
                <input
                    type="number"
                    id="spec-{{ $spec->id }}-min"
                    name="specs[{{ $spec->id }}][min]"
                    value="{{ is_scalar($minimum) ? $minimum : '' }}"
                    wire:model.live.debounce.500ms="specs.{{ $spec->id }}.min"
                    placeholder="{{ __('catalog.filters.min') }}"
                />

                <label class="visually-hidden" for="spec-{{ $spec->id }}-max">
                    {{ __('catalog.filters.spec_max', ['name' => $spec->name]) }}
                </label>
                <input
                    type="number"
                    id="spec-{{ $spec->id }}-max"
                    name="specs[{{ $spec->id }}][max]"
                    value="{{ is_scalar($maximum) ? $maximum : '' }}"
                    wire:model.live.debounce.500ms="specs.{{ $spec->id }}.max"
                    placeholder="{{ __('catalog.filters.max') }}"
                />
            </div>
        </x-filter-group>
    @elseif ($spec->isBoolean())
        {{-- A yes/no is one question with three answers: yes, no, and not asked. --}}
        @php
            $answer = data_get($filters, "specs.{$spec->id}");
            $answer = is_scalar($answer) ? (string) $answer : '';
        @endphp

        <x-filter-group :title="$spec->title()">
            <label class="visually-hidden" for="spec-{{ $spec->id }}"> {{ $spec->name }} </label>

            <select
                class="filter-select"
                id="spec-{{ $spec->id }}"
                name="specs[{{ $spec->id }}]"
                wire:model.live="specs.{{ $spec->id }}"
            >
                <option value="1" @selected($answer === '1')>{{ __('catalog.product.spec_yes') }}</option>
                <option value="0" @selected($answer === '0')>{{ __('catalog.product.spec_no') }}</option>
                <option value="" @selected($answer === '')>{{ __('catalog.filters.choose') }}</option>
            </select>
        </x-filter-group>
    @else
        <x-filter-dropdown
            :title="$spec->title()"
            :name="'specs['.$spec->id.'][]'"
            :wire-model="'specs.'.$spec->id"
            :options="$spec->values"
            :selected="$selected"
        />
    @endif
@endforeach
