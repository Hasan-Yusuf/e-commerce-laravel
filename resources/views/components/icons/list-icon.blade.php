@props(['size' => 24, 'class' => ''])

<svg
    {{ $attributes->class(['icon', $class]) }}
    xmlns="http://www.w3.org/2000/svg"
    height="{{ $size }}px"
    viewBox="0 -960 960 960"
    width="{{ $size }}px"
    fill="currentColor"
>
    <path d="M360-600v-80h480v80H360Zm0 160v-80h480v80H360Zm0 160v-80h480v80H360ZM160-600v-80h80v80h-80Zm0 160v-80h80v80h-80Zm0 160v-80h80v80h-80Z" />
</svg>
