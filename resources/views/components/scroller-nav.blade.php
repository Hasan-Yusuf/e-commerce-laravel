{{-- A scroller's own way along: two buttons that step a row sideways and go deaf
     at the ends of it.

     The same pair works for a hero that shows one slide at a time and for a
     section that shows a row of cards: how far a step is, and which way it goes
     when the page is read right to left, is the scroller's business, and the
     script reads it off whatever is beside this.

     They are wired by script rather than by markup — the row is found from the
     group both sit in — so a browser without JavaScript is not offered a control
     that would do nothing; the row is still dragged, wheeled and swiped
     through. --}}

@props([
    'variant' => null,
])

{{-- The frame the buttons hang in is the wide wrapper rather than the page's own
     edge: the buttons stop at the measure the site's widest content is built
     to, so they never bleed to the edge of a wide screen. --}}
<div
    @class([
        'scroller-nav wrapper wrapper--wide',
        'scroller-nav--on-action' => $variant === 'on-action',
    ])
    data-scroller-nav
    role="group"
    aria-label="{{ __('homepage.carousel.label') }}"
>
    <x-button
        variant="bare"
        class="scroller-nav__button"
        data-scroller-step="previous"
        :aria-label="__('homepage.carousel.previous')"
    >
        <x-icons.chevron-left-icon :size="20" class="scroller-nav__icon" aria-hidden="true" />
    </x-button>

    <x-button
        variant="bare"
        class="scroller-nav__button"
        data-scroller-step="next"
        :aria-label="__('homepage.carousel.next')"
    >
        <x-icons.chevron-right-icon :size="20" class="scroller-nav__icon" aria-hidden="true" />
    </x-button>
</div>
