@php
    $selectedDelivery = old('delivery_method', $summary->deliveryMethod->value);
@endphp

<form class="checkout-form form" method="POST" action="{{ route('checkout.delivery.store') }}" data-submit-once>
    @csrf

    <fieldset class="form-field form-field--radio">
        <legend class="visually-hidden">{{ __('checkout.delivery.legend') }}</legend>

        @foreach (\App\Enums\DeliveryMethod::cases() as $method)
            <x-inputs.radio
                name="delivery_method"
                :value="$method->value"
                :label="$method->getLabel()"
                :description="$method->description()"
                :checked="$selectedDelivery === $method->value"
                required
            />
        @endforeach
    </fieldset>

    <x-form-error name="delivery_method" />

    <div class="form-actions">
        <x-button variant="secondary" :href="$previousStep?->url()">
            {{ __('checkout.actions.back') }}
        </x-button>

        <x-button type="submit"> {{ __('checkout.actions.continue') }} </x-button>
    </div>
</form>
