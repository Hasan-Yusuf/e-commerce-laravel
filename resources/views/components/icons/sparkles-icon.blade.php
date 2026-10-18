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
    stroke-linejoin="round"
>
    <path d="M11 3.5 12.5 8l4.5 1.5L12.5 11 11 15.5 9.5 11 5 9.5 9.5 8 11 3.5Z" />
    <path d="M17.5 14.5l.9 2.5 2.6.9-2.6.9-.9 2.6-.9-2.6-2.6-.9 2.6-.9.9-2.5Z" />
</svg>
