@php
    $formatPrice = \App\Support\Money::fromFils(...);

    // The form steps this review links back to, so their names and URLs are
    // never typed by hand.
    $customerStep = \App\Enums\CheckoutStep::CUSTOMER;
    $addressStep = \App\Enums\CheckoutStep::SHIPPING_ADDRESS;
    $deliveryStep = \App\Enums\CheckoutStep::DELIVERY;
    $paymentStep = \App\Enums\CheckoutStep::PAYMENT;

    // The number each step prints, keyed by its own value: the flow's numbering
    // lives on the state, so a step the flow lifts leaves no gap in the count.
    $stepNumbers = [];

    foreach ($steps as $item) {
        $stepNumbers[$item['step']->value] = $item['number'];
    }
@endphp

<div class="review-blocks">
    {{-- The placement was refused because the stock moved on under the shopper:
         the cart below has been brought in line with it, and the order can be
         placed again from here. --}}
    @if (session('stock_changed'))
        <x-alert
            type="warning"
            :title="__('checkout.place.stock_changed_title')"
            :message="__('checkout.place.stock_changed_message')"
        />
    @endif

    <x-review-block class="review-block--wide" :title="__('checkout.review.items')" :edit-href="route('cart.index')">
        <ul class="review-items">
            @foreach ($summary->cart->lines as $line)
                <li class="line-item">
                    <span class="line-item__name">
                        {{ $line->product?->name ?? __('carts.item.unavailable_product') }}

                        <span class="line-item__quantity">
                            {{ __('checkout.review.quantity', ['count' => $line->quantity]) }}
                        </span>
                    </span>

                    @if ($line->product)
                        <x-stock-badge :product="$line->product" />
                    @endif

                    <span class="line-item__total"> {{ $formatPrice($line->lineTotalInFils()) }} </span>

                    @if ($line->priceChanged())
                        <span class="line-item__notice">
                            {{
                                __('carts.item.price_changed', [
                                    'old' => $formatPrice($line->snapshotPriceInFils),
                                    'new' => $formatPrice($line->currentPriceInFils),
                                ])
                            }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
    </x-review-block>

    <x-review-block :step="$customerStep" :number="$stepNumbers[$customerStep->value] ?? null" :edit-href="$customerStep->url()">
        <p class="review-block__line">{{ $summary->customer->name }}</p>
        <p class="review-block__line">{{ $summary->customer->email }}</p>

        {{-- A number is the shopper's to give: nothing is printed for one they
             did not. --}}
        @if ($summary->customer->fullPhone() !== '')
            <p class="review-block__line">{{ $summary->customer->fullPhone() }}</p>
        @endif
    </x-review-block>

    {{-- Collecting the order from the shop takes the address step out of the
         flow, so there is no answer to restate here. --}}
    @unless ($summary->deliveryMethod->is(\App\Enums\DeliveryMethod::PICKUP))
        <x-review-block :step="$addressStep" :number="$stepNumbers[$addressStep->value] ?? null" :edit-href="$addressStep->url()">
            @foreach ($summary->shippingAddress->lines() as $line)
                <p class="review-block__line">{{ $line }}</p>
            @endforeach
        </x-review-block>
    @endunless

    <x-review-block :step="$deliveryStep" :number="$stepNumbers[$deliveryStep->value] ?? null" :edit-href="$deliveryStep->url()">
        <p class="review-block__line">{{ $summary->deliveryMethod->getLabel() }}</p>
        <p class="review-block__line">{{ $summary->deliveryMethod->description() }}</p>
    </x-review-block>

    <x-review-block :step="$paymentStep" :number="$stepNumbers[$paymentStep->value] ?? null" :edit-href="$paymentStep->url()">
        <p class="review-block__line">{{ $summary->paymentMethod->getLabel() }}</p>
        <p class="review-block__line">{{ $summary->paymentMethod->description() }}</p>
    </x-review-block>

    @guest
        <div class="review-signin">
            <p class="review-signin__notice">{{ __('checkout.place.guest_notice') }}</p>

            <div class="review-signin__actions">
                <x-button :href="route('login')"> {{ __('components.header.login') }} </x-button>

                <x-button variant="secondary" :href="route('register')">
                    {{ __('components.header.signup') }}
                </x-button>
            </div>
        </div>
    @endguest

    <form class="checkout-form form" method="POST" action="{{ route('checkout.place') }}" data-submit-once>
        @csrf

        {{-- The attempt's own identity: submitting it twice — a refresh, the
             back button, a double click — is recognised as this same order
             rather than a second one. --}}
        <x-inputs.hidden name="checkout_token" :value="$summary->token" />

        <x-inputs.textarea name="notes" rows="3" :label="__('checkout.place.note')" />

        <div class="form-actions">
            <x-button type="submit" block> {{ __('checkout.place.action') }} </x-button>
        </div>
    </form>
</div>
