<x-layouts::app :title="__('auth.forgot.title')">
    @pushOnce('styles')
        @vite('resources/css/features/auth.css')
    @endPushOnce

    <x-auth-card class="grid-center" :title="__('auth.forgot.title')" :subtitle="__('auth.forgot.subtitle')">
        <p class="auth-card__message">{{ __('auth.forgot.message') }}</p>

        <div class="form-actions">
            <x-button :href="route('login')" block> {{ __('auth.forgot.back') }} </x-button>
        </div>
    </x-auth-card>
</x-layouts::app>
