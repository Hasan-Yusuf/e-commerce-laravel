<form class="checkout-form form" method="POST" action="{{ route('checkout.payment.store') }}" data-submit-once>
    @csrf

    <x-payment-method :selected="$summary->paymentMethod->value" required />

    <div class="form-actions">
        <x-button variant="secondary" :href="$previousStep?->url()">
            {{ __('checkout.actions.back') }}
        </x-button>

        <x-button type="submit"> {{ __('checkout.actions.continue') }} </x-button>
    </div>
</form>
