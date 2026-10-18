@props([
    'name' => 'payment_method',
    'selected' => null,
])

@php
    $methods = app(\App\Services\Payments\PaymentGatewayRegistry::class)->methods();
    $selected = old($name, $selected);
    $errorId = str_replace(['[', ']', '.'], ['-', '', '-'], $name).'-error';
    $hasError = $errors->has($name);
@endphp

<fieldset class="form-field form-field--radio" @if ($hasError) aria-describedby="{{ $errorId }}" @endif>
    <legend class="visually-hidden">{{ __('checkout.payment.legend') }}</legend>

    @foreach ($methods as $method)
        <x-inputs.radio
            :name="$name"
            :value="$method->value"
            :label="$method->getLabel()"
            :description="$method->description()"
            :checked="$selected === $method->value"
            {{ $attributes }}
        />
    @endforeach
</fieldset>

<x-form-error :name="$name" :id="$errorId" />
