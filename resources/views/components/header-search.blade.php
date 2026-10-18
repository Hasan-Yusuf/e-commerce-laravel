@props([
    'id' => 'search',
    'value' => null,
])

@php
    $value ??= is_string(request('q')) ? request('q') : '';
@endphp

{{-- Extra attributes are merged so a placement can add its own modifier
     (e.g. `site-header__search--nav`). --}}
<form
    {{ $attributes->class(['site-header__search']) }}
    role="search"
    method="GET"
    action="{{ route('products.index') }}"
>
    <div class="form-field">
        <label for="{{ $id }}" class="visually-hidden"> {{ __('components.search_input.label') }} </label>

        <input
            class="search-input__field"
            type="text"
            id="{{ $id }}"
            name="q"
            placeholder="{{ __('components.search_input.placeholder') }}"
            value="{{ $value }}"
        />

        <x-button variant="ghost" class="search-input__button" type="submit">
            <x-icons.search-icon class="search-input__icon" aria-hidden="true" />
            <span class="visually-hidden">{{ __('components.search_input.button') }}</span>
        </x-button>
    </div>
</form>
