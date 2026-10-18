{{--
    The picture a piece of media puts on a page.

    The data is ready to render: App\Services\ImageService has already resolved
    the WebP version the uploads are converted into — or the file as it was
    stored when that conversion is missing, so a picture always has something to
    draw — and this component draws what it is handed rather than choosing
    between the two itself.

    Where a media record keeps responsive variants, every one of them is offered
    to the browser, so a card draws a thumbnail-sized file rather than the
    original the shop uploaded. A record that keeps none is left as the plain
    original, which is what the browser would have fetched anyway.

    How wide the image is drawn is the caller's to say, because only the caller
    knows: `sizes` is that answer, and without it the browser has to assume the
    image spans the whole viewport and picks a variant to match.

    Whether the image is one the page waits on is the caller's to say too. The
    first images a page shows are worth fetching with the document and asking
    for first, and everything further down is worth leaving until it is scrolled
    to.
--}}
@props([
    // The image to draw, or null when there is none: a card with no image is
    // simply a card with no image.
    'image' => null,
    'alt' => '',
    // The widths the image is laid out at, for the browser to size its choice
    // by, as the `sizes` attribute spells them. Left off, the browser assumes
    // the viewport is what the image spans.
    'sizes' => null,
    // Whether this image is one the page is waiting on.
    'priority' => false,
])

@if ($image)
    <img
        src="{{ $image->src }}"
        @if ($image->srcset) srcset="{{ $image->srcset }}" @endif
        @if ($sizes) sizes="{{ $sizes }}" @endif
        alt="{{ $alt }}"
        @if ($priority)
            fetchpriority="high"
        @else
            loading="lazy"
        @endif
        decoding="async"
        {{ $attributes }}
    />
@endif
