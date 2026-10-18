@props([
    'type' => 'info',
    'title' => null,
    'message' => null,
])

<div {{ $attributes->class(['alert', 'alert--'.$type]) }} role="alert">
    <div class="alert__header">
        @switch ($type)
            @case ('success')
                <x-icons.success-icon class="alert__icon" aria-hidden="true" />
                @break
            @case ('warning')
                <x-icons.warning-icon class="alert__icon" aria-hidden="true" />
                @break
            @case ('danger')
                <x-icons.danger-icon class="alert__icon" aria-hidden="true" />
                @break
            @default
                <x-icons.info-icon class="alert__icon" aria-hidden="true" />
        @endswitch

        @if ($title)
            <span class="alert__title">{{ $title }}</span>
        @endif
    </div>

    @if ($message)
        <div class="alert__body">
            <p>{{ $message }}</p>
        </div>
    @endif

    @isset($actions)
        <div class="alert__actions">{{ $actions }}</div>
    @endisset
</div>
