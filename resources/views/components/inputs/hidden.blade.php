@props([
    'name',
    'value' => null,
])

{{-- A field the shopper never sees: it carries a value the form has to send —
     the checkout attempt's token, the product a POST acts on, or the choices the
     page's own controls must keep. It has no label, hint or error of its own, so
     it renders nothing but the field itself. --}}
<input type="hidden" name="{{ $name }}" value="{{ $value }}" {{ $attributes->except('type') }} />
