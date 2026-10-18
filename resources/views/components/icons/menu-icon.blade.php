@props(['size' => 24, 'class' => ''])

<svg
    {{ $attributes->class(['icon', $class]) }}
    xmlns="http://www.w3.org/2000/svg"
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
>
    <path d="M4 6h16M4 12h16M4 18h16" />
</svg>
