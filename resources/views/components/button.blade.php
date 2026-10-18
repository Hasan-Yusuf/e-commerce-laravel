@props([
    'href' => null,
    'variant' => 'primary',
    'size' => null,
    'block' => false,
    'type' => 'button',
])

@php
    // "bare" renders the element with only the caller's classes (no .btn),
    // for custom-styled controls such as icon buttons.
    $classes = $variant === 'bare'
        ? []
        : [
            'btn',
            'btn--'.$variant => $variant !== 'primary',
            'btn--'.$size => $size !== null,
            'btn--block' => $block,
        ];
@endphp

@if ($href !== null)
    <a {{ $attributes->class($classes)->merge(['href' => $href]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->class($classes)->merge(['type' => $type]) }}>{{ $slot }}</button>
@endif
