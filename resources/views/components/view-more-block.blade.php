@props([
    'href',
    'label' => null,
])

<a href="{{ $href }}" class="view-more"> {{ $label ?? __('homepage.view_more') }} </a>
