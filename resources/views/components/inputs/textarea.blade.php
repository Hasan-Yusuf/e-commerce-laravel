@props([
    'name',
    'label' => null,
    'id' => null,
    'wrapperClass' => null,
    'showErrors' => true,
    'errorBag' => 'default',
])

@php
    $value = $slot->isEmpty() ? old($name) : $slot;

    $wiring = \App\Support\FormFieldWiring::for(
        name: $name,
        id: $id,
        attributes: $attributes,
        errors: new \App\Support\FormFieldErrors($errors, $errorBag, $showErrors),
    );

    $inputId = $wiring['inputId'];
    $errorId = $wiring['errorId'];
    $required = $wiring['required'];
    $defaults = $wiring['defaults'];
@endphp

<div @class(['form-field', $wrapperClass])>
    @include('components.inputs.partials.field-label')

    <textarea name="{{ $name }}" {{ $attributes->except('aria-describedby')->merge($defaults) }}>{{ $value }}</textarea>

    @include('components.inputs.partials.field-messages')
</div>
