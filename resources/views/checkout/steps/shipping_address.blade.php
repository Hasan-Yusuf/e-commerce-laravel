<form class="checkout-form form" method="POST" action="{{ route('checkout.shipping.store') }}" data-submit-once>
    @csrf

    <x-address-form :address="$summary->shippingAddress" :savable="auth()->check()" />

    <div class="form-actions">
        <x-button variant="secondary" :href="$previousStep?->url()">
            {{ __('checkout.actions.back') }}
        </x-button>

        <x-button type="submit"> {{ __('checkout.actions.continue') }} </x-button>
    </div>
</form>
