{{--
    The label over a field: what it asks for, and the required note that a
    screen reader keeps when the mark on the label is only a visual convention.
    Included by every text-like field component, so the label is drawn the same
    way wherever it appears.
--}}
@if ($label !== null)
    <label for="{{ $inputId }}">
        {{ $label }}

        @if ($required)
            <span class="visually-hidden"> ({{ __('components.form.required') }}) </span>
        @endif
    </label>
@endif
