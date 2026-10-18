@props([
    'title',
    'href' => null,
    'label' => null,
    'size' => null,
    // The mark over the title, named as a component: "icons.star-icon".
    'icon' => null,
])

<header class="section-header">
    <h2 @class([
        'section-title',
        'section-title--'.$size => $size,
    ])>
        @if ($icon)
            <x-dynamic-component :component="$icon" class="section-title__icon" aria-hidden="true" />
        @endif

        {{ $title }}
    </h2>

    @if ($href)
        <x-view-more-block :href="$href" :label="$label" />
    @endif
</header>
