@props([
    'name',
    'label' => null,
    'type' => 'text',
    'id' => null,
    'wrapperClass' => null,
    'showErrors' => true,
    'errorBag' => 'default',
])

@php
    // Default the value to the old input, except for passwords.
    $wiring = \App\Support\FormFieldWiring::for(
        name: $name,
        id: $id,
        attributes: $attributes,
        errors: new \App\Support\FormFieldErrors($errors, $errorBag, $showErrors),
        value: $type !== 'password' ? old($name) : null,
    );

    $inputId = $wiring['inputId'];
    $errorId = $wiring['errorId'];
    $required = $wiring['required'];
    $defaults = $wiring['defaults'];
@endphp

<div @class(['form-field', $wrapperClass])>
    @include('components.inputs.partials.field-label')

    <input type="{{ $type }}" name="{{ $name }}" {{ $attributes->except('aria-describedby')->merge($defaults) }} />

    @include('components.inputs.partials.field-messages')
</div>
