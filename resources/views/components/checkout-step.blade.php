@props([
    // The step whose markup this wraps.
    'step',
    // The number the step prints in this checkout's flow — the state decides it,
    // because a step the flow lifts leaves no gap in the count.
    'number' => null,
    // current | complete | pending
    'state' => 'current',
])

@php
    $stateLabel = match ($state) {
        'complete' => __('checkout.steps.state.complete'),
        'pending' => __('checkout.steps.state.pending'),
        default => __('checkout.steps.state.current'),
    };
@endphp

<section
    {{ $attributes->class(['checkout-step', 'checkout-step--'.$state]) }}
    @if ($state === 'current') aria-current="step" @endif
>
    <header class="checkout-step__header">
        <span class="checkout-step__number" aria-hidden="true">{{ $number }}</span>

        <h2 class="checkout-step__title">
            {{ $step->label() }}

            {{-- The state is what the number and the styling say visually, so it
                 is spelled out for a screen reader. --}}
            <span class="visually-hidden">{{ $stateLabel }}</span>
        </h2>
    </header>

    <div class="checkout-step__body">{{ $slot }}</div>
</section>
