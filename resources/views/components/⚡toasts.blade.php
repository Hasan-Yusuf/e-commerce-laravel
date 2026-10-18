<?php

use App\DTOs\FlashMessage;
use App\Enums\ToastType;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The global toast stack (bottom inline-end). Toasts are pushed by the `notify`
 * event or from flashed session data, and dismiss themselves after a timeout.
 */
new class extends Component
{
    /**
     * @var array<int, array{id: string, type: string, title: ?string, body: ?string, duration: int}>
     */
    public array $toasts = [];

    public function mount(): void
    {
        if ($flashed = FlashMessage::fromSession()) {
            $this->push(...$flashed->toArray());
        }

        if ($message = session('errors')?->first()) {
            $this->push(type: ToastType::DANGER, title: $message);
        }
    }

    #[On('notify')]
    public function notify(
        ?string $type = null,
        ?string $title = null,
        ?string $body = null,
        int $duration = 6000,
    ): void {
        $this->push(
            type: ToastType::fromValue($type),
            title: $title,
            body: $body,
            duration: $duration,
        );
    }

    public function dismiss(string $id): void
    {
        $this->toasts = array_values(
            array_filter(
                $this->toasts,
                fn (array $toast): bool => $toast['id'] !== $id,
            ),
        );
    }

    private function push(
        ToastType|string $type,
        ?string $title = null,
        ?string $body = null,
        int $duration = 6000,
    ): void {
        $this->toasts[] = [
            'id' => (string) Str::uuid(),
            // Stored as the type's own word: the stack is a public Livewire
            // property, so what crosses to the browser is plain data, and a word
            // the stack does not know is shown as information rather than as a
            // class that matches nothing.
            'type' => ToastType::fromValue($type)->value,
            'title' => $title,
            'body' => $body,
            'duration' => $duration,
        ];
    }
};
?>

<div class="toast-stack" role="region" aria-label="{{ __('components.toasts.region') }}" aria-live="polite">
    @foreach ($toasts as $toast)
        @php($type = \App\Enums\ToastType::fromValue($toast['type']))

        <div
            class="toast toast--{{ $type->value }}"
            wire:key="toast-{{ $toast['id'] }}"
            x-data
            x-init="setTimeout(() => $wire.dismiss('{{ $toast['id'] }}'), {{ $toast['duration'] }})"
            role="alert"
        >
            <div class="toast__header">
                <x-dynamic-component :component="$type->icon()" class="toast__icon" aria-hidden="true" />

                <span class="toast__title">{{ $toast['title'] }}</span>

                <button
                    type="button"
                    class="toast__close"
                    wire:click="dismiss('{{ $toast['id'] }}')"
                    aria-label="{{ __('components.toasts.dismiss') }}"
                >
                    <x-icons.close-icon class="toast__close-icon" aria-hidden="true" />
                </button>
            </div>

            @if ($toast['body'])
                <p class="toast__body">{{ $toast['body'] }}</p>
            @endif

            <span
                class="toast__progress"
                style="animation-duration: {{ $toast['duration'] }}ms"
                aria-hidden="true"
            ></span>
        </div>
    @endforeach
</div>
