@props(['size' => 24, 'class' => ''])

<svg
    {{ $attributes->class(['icon', $class]) }}
    xmlns="http://www.w3.org/2000/svg"
    height="{{ $size }}px"
    viewBox="0 -960 960 960"
    width="{{ $size }}px"
    fill="currentColor"
>
    <path d="M120-520v-320h320v320H120Zm0 400v-320h320v320H120Zm400-400v-320h320v320H520Zm0 400v-320h320v320H520Z" />
</svg>
