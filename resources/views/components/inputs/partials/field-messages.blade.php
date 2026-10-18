{{--
    What is shown under a field: this field's error, and the caller's hint. The
    error's id is what `aria-describedby` points at, so the two are read as one
    when the field is described. Included by every text-like field component.
--}}
@if ($showErrors)
    <x-form-error :name="$name" :id="$errorId" :error-bag="$errorBag" />
@endif

@isset($hint)
    <div class="form-field__hint">{{ $hint }}</div>
@endisset
