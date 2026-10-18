@props([
    // The step this block restates: it names the block and numbers it. The
    // items are not a step, so that block titles itself instead.
    'step' => null,
    'title' => null,
    // The number the step prints in this checkout's flow, or null for a block
    // that is not a step.
    'number' => null,
    // Where the block is corrected, and what the button says it edits. A block
    // with no href ends without one.
    'editHref' => null,
    'editSubject' => null,
])

@php
    $heading = $title ?? $step->label();
@endphp

<section {{ $attributes->class(['card', 'review-block']) }}>
    {{-- The block wears a step's own header, number and title, so the review
         reads as the answers to the steps it restates. --}}
    <header class="checkout-step__header">
        @if ($step !== null)
            <span class="checkout-step__number" aria-hidden="true">{{ $number }}</span>
        @endif

        <h3 class="checkout-step__title">{{ $heading }}</h3>
    </header>

    <div class="review-block__body">{{ $slot }}</div>

    @if ($editHref !== null)
        <div class="review-block__actions">
            <x-button variant="secondary" :href="$editHref">
                {{
                    __('checkout.actions.edit', [
                        'subject' => $editSubject ?? $heading,
                    ])
                }}
            </x-button>
        </div>
    @endif
</section>
