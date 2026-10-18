@props([
    'banner',
])

{{-- Render a link when the banner targets one, otherwise a plain slide --}}
@if ($banner->link)
    <a {{ $attributes->merge(['class' => 'banner-slider__slide']) }} href="{{ $banner->link }}"> {{ $slot }} </a>
@else
    <div {{ $attributes->merge(['class' => 'banner-slider__slide']) }}>{{ $slot }}</div>
@endif
