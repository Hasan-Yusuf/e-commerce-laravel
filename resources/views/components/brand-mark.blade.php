{{--
    A brand's mark: the logo the shop uploaded for the scheme the page is read
    in, or the brand name where that scheme has no logo.

    A shop may upload a mark for each colour scheme — a black logo for light
    pages, a white logo for dark ones. Where it uploaded only one, the name
    stands in on the other scheme, so a mark is never drawn against a background
    it cannot be read on. Where it uploaded none, the name is simply the mark.

    The mark is drawn bare — no badge around it. Which of the two is drawn is
    decided in CSS (see brand-logo.css) from the reader's colour scheme, so the
    page needs no JavaScript to choose.
--}}
@props([
    'name',
    // The logo to draw on a light page (the black mark), when the shop has one.
    'lightLogo' => null,
    // The logo to draw on a dark page (the white mark), when the shop has one.
    'darkLogo' => null,
])

@if ($lightLogo === null && $darkLogo === null)
    <span class="brand-name">{{ $name }}</span>
@else
    @if ($lightLogo)
        <x-media-image :image="$lightLogo" :alt="$name" sizes="96px" class="brand-logo brand-logo--for-light" />
    @else
        <span class="brand-name brand-name--for-light">{{ $name }}</span>
    @endif

    @if ($darkLogo)
        <x-media-image :image="$darkLogo" :alt="$name" sizes="96px" class="brand-logo brand-logo--for-dark" />
    @else
        <span class="brand-name brand-name--for-dark">{{ $name }}</span>
    @endif
@endif
