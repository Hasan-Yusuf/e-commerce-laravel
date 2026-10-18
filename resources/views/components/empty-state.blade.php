@props([
    'icon' => 'info',
    'title' => null,
    'message' => null,
    'href' => null,
    'action' => null,
])

<div class="empty-state">
    <x-dynamic-component :component="'icons.'.$icon.'-icon'" class="empty-state__icon" aria-hidden="true" />

    @if ($title)
        <p class="empty-state__title">{{ $title }}</p>
    @endif

    @if ($message)
        <p class="empty-state__message">{{ $message }}</p>
    @endif

    @if ($href && $action)
        <x-button variant="secondary" :href="$href">{{ $action }}</x-button>
    @endif
</div>
