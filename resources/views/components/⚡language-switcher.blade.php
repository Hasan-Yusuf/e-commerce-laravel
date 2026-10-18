<?php

use Livewire\Component;

new class extends Component
{
    public function changeLocale(string $locale)
    {
        $locales = config('locales', []);

        if (array_key_exists($locale, $locales)) {
            session(['locale' => $locale]);

            $this->redirect(request()->header('Referer') ?? url()->previous());
        }
    }

    public function render()
    {
        return view('components.⚡language-switcher', [
            'locales' => config('locales', []),
            'currentLocale' => app()->getLocale(),
        ]);
    }
};
?>

<div>
    <x-filament::dropdown placement="bottom-end" teleport>
        <x-slot name="trigger">
            <x-filament::button color="gray" kind="tertiary" icon="heroicon-m-language" icon-alias="language-switcher">
                {{ $locales[$currentLocale]['label'] ?? strtoupper($currentLocale) }}
            </x-filament::button>
        </x-slot>

        <x-filament::dropdown.list>
            @foreach ($locales as $code => $data)
                <x-filament::dropdown.list.item
                    wire:click="changeLocale('{{ $code }}')"
                    :icon="$currentLocale === $code ? 'heroicon-m-check' : null"
                >
                    {{ $data['label'] }}
                </x-filament::dropdown.list.item>
            @endforeach
        </x-filament::dropdown.list>
    </x-filament::dropdown>
</div>
