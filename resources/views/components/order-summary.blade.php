@props([
    // The order to restate: its lines, where it is delivered, how it is paid
    // for and what it costs. Used by the confirmation and the order details, so
    // both read the order back the same way.
    'order',
])

@php
    // The method the order was placed with: a payment row is written as the
    // order is placed, so the first of them is that method.
    $payment = $order->payments->first();

    $addressLines = $order->shippingAddress === null
        ? []
        : \App\DTOs\ShippingAddressData::fromAddress(
            $order->shippingAddress,
        )->lines();

    $discount = $order->discount_amount_in_fils;
@endphp

<section {{ $attributes->class(['card', 'order-summary']) }}>
    <h2 class="order-summary__title">{{ __('orders.summary.title') }}</h2>

    <ul class="order-summary__items">
        @foreach ($order->orderItems as $item)
            <x-order-item :item="$item" />
        @endforeach
    </ul>

    <dl class="summary">
        <div class="summary__row">
            <dt>{{ __('orders.summary.subtotal') }}</dt>
            <dd>{{ $order->subtotal_in_fils }}</dd>
        </div>

        <div class="summary__row">
            <dt>{{ __('orders.summary.shipping') }}</dt>
            <dd>{{ $order->shipping_amount_in_fils }}</dd>
        </div>

        <div class="summary__row">
            <dt>{{ __('orders.summary.tax') }}</dt>
            <dd>{{ $order->tax_amount_in_fils }}</dd>
        </div>

        {{-- A discount is not charged yet, so the row only appears when one
             actually was: the summary never lists a saving that is not there. --}}
        @if ($discount->isPositive())
            <div class="summary__row">
                <dt>{{ __('orders.summary.discount') }}</dt>
                <dd>−{{ $discount }}</dd>
            </div>
        @endif

        <div class="summary__row summary__row--total">
            <dt>{{ __('orders.summary.total') }}</dt>
            <dd>{{ $order->total_amount_in_fils }}</dd>
        </div>
    </dl>

    {{-- A collection order has no address to restate: the block only appears
         when there is one. --}}
    @if ($addressLines !== [])
        <div class="order-summary__block">
            <h3 class="order-summary__block-title">{{ __('orders.show.address') }}</h3>

            @foreach ($addressLines as $line)
                <p class="order-summary__line">{{ $line }}</p>
            @endforeach
        </div>
    @endif

    {{-- How the order is to be fulfilled, as the shopper chose it: the same
         pair the payment block prints. --}}
    <div class="order-summary__block">
        <h3 class="order-summary__block-title">{{ __('orders.show.delivery') }}</h3>

        <p class="order-summary__line">{{ $order->delivery_method->getLabel() }}</p>

        <p class="order-summary__line">{{ $order->delivery_method->description() }}</p>
    </div>

    {{-- The shopper's own word about the order, when they left one with it. --}}
    @if (filled($order->notes))
        <div class="order-summary__block">
            <h3 class="order-summary__block-title">{{ __('orders.show.note') }}</h3>

            <p class="order-summary__line order-summary__line--note">{{ $order->notes }}</p>
        </div>
    @endif

    @if ($payment !== null)
        <div class="order-summary__block">
            <h3 class="order-summary__block-title">{{ __('orders.show.payment') }}</h3>

            <p class="order-summary__line">{{ $payment->method->getLabel() }}</p>

            <p class="order-summary__line">{{ $payment->method->description() }}</p>
        </div>
    @endif
</section>
