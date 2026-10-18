@props([
    'summary',
    // The cart's own summary leads to the checkout; the checkout page shows the
    // same totals without the button, which would only link to itself.
    'showCheckout' => true,
])

@php
    $formatPrice = \App\Support\Money::fromFils(...);
@endphp

<aside class="card cart-summary">
    <h2 class="cart-summary__title">{{ __('carts.summary.title') }}</h2>

    <dl class="summary">
        <div class="summary__row">
            <dt>{{ __('carts.summary.subtotal') }}</dt>
            <dd>{{ $formatPrice($summary->subtotalInFils) }}</dd>
        </div>

        {{-- The applied coupon reads as a reduction of its own, above the rule
             that closes the totals: the code it was applied under and the money
             it took off. --}}
        @if ($summary->coupon !== null)
            <div class="summary__row summary__row--discount">
                <dt>{{ $summary->coupon->code }}</dt>

                <dd>−{{ $formatPrice($summary->discountInFils) }}</dd>
            </div>
        @endif

        <div class="summary__row summary__row--total">
            <dt>{{ __('carts.summary.total') }}</dt>
            <dd>{{ $formatPrice($summary->totalInFils()) }}</dd>
        </div>
    </dl>

    {{-- The way on to the checkout comes before the coupon: the code is the
         extra, not the thing being asked for. --}}
    @if ($showCheckout)
        <x-button block :href="route('checkout.index')"> {{ __('carts.summary.checkout') }} </x-button>
    @endif

    {{-- The code is entered under the checkout action, on one line with the
         button that answers it. An applied one turns the field into a read-only
         restatement of itself and the button that applied it into the one that
         takes it back off. --}}
    @if ($summary->coupon === null)
        <form class="cart-summary__coupon" method="POST" action="{{ route('checkout.coupon.store') }}" data-submit-once>
            @csrf

            <x-inputs.text name="code" :label="__('carts.summary.coupon')" :placeholder="__('checkout.coupon.placeholder')" required />

            <x-button type="submit" variant="tertiary"> {{ __('checkout.coupon.apply') }} </x-button>
        </form>
    @else
        <form class="cart-summary__coupon" method="POST" action="{{ route('checkout.coupon.remove') }}">
            @csrf

            <x-inputs.text name="code" :label="__('carts.summary.coupon')" :value="$summary->coupon->code" readonly />

            <x-button type="submit" variant="tertiary"> {{ __('checkout.coupon.remove') }} </x-button>
        </form>
    @endif
</aside>
