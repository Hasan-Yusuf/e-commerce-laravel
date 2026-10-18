@props([
    'items' => null,
])

@php
    $items = $items ?? app(\App\Services\BreadcrumbsService::class)->forCurrentRoute();
@endphp

@if (count($items) > 1)
    <nav class="breadcrumbs wrapper" aria-label="{{ __('catalog.breadcrumbs.aria') }}">
        <ol class="breadcrumbs__list">
            @foreach ($items as $item)
                <li class="breadcrumbs__item">
                    @if (! $loop->last && ! empty($item['url']))
                        <a class="breadcrumbs__link" href="{{ $item['url'] }}"> {{ $item['label'] }} </a>
                    @else
                        <span
                            class="breadcrumbs__current"
                            @if ($loop->last) aria-current="page" @endif
                        >{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
