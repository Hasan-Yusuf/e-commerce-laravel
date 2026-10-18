<x-layouts::app :title="__('checkout.title')">
    @pushOnce('styles')
        {{-- The order summary card is the cart page's (features/cart.css); the
             checkout reuses it rather than restating it. --}}
        @vite([
            'resources/css/features/cart.css',
            'resources/css/features/checkout.css',
        ])
    @endPushOnce

    <div class="checkout">
        <header class="checkout__header page-header page-header--spaced wrapper">
            <h1 class="page-header__title">{{ __('checkout.title') }}</h1>

            <p class="page-header__subtitle">{{ __('checkout.subtitle') }}</p>
        </header>

        {{-- The map of the flow: where the shopper is, and what is already
             answered (those steps link back to the form that filled them). --}}
        <ol class="checkout-steps wrapper" aria-label="{{ __('checkout.steps.label') }}">
            @foreach ($steps as $item)
                <li
                    class="checkout-steps__item checkout-steps__item--{{ $item['state'] }}"
                    @if ($item['state'] === 'current') aria-current="step" @endif
                >
                    @if ($item['state'] === 'complete')
                        <a class="checkout-steps__link" href="{{ $item['step']->url() }}">
                            <span class="checkout-steps__number" aria-hidden="true">{{ $item['number'] }}</span>

                            <span class="checkout-steps__title"> {{ $item['step']->label() }} </span>
                        </a>
                    @else
                        <span class="checkout-steps__number" aria-hidden="true">{{ $item['number'] }}</span>

                        <span class="checkout-steps__title"> {{ $item['step']->label() }} </span>
                    @endif
                </li>
            @endforeach
        </ol>

        {{-- The step and its summary want the wide measure; the heading and
             the flow map above keep the page's own, so the two widths line up
             with the rest of the site. --}}
        <div class="checkout-layout wrapper wrapper--wide">
            <div class="checkout-layout__main">
                {{-- Only the step being shown is rendered, so it is the current
                     one; the partial under it answers it. --}}
                <x-checkout-step :step="$step" :number="$stepNumber">
                    @include($step->view())
                </x-checkout-step>
            </div>

            <x-cart-summary :summary="$summary->cart" :show-checkout="false" />
        </div>
    </div>
</x-layouts::app>
