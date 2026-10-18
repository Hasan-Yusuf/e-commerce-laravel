<x-layouts::app :title="__('auth.login.title')">
    @pushOnce('styles')
        @vite('resources/css/features/auth.css')
    @endPushOnce

    <x-auth-card class="grid-center" :title="__('auth.login.title')" :subtitle="__('auth.login.subtitle')">
        {{-- The dialog renders this same form (auth-login-form.blade.php). --}}
        <x-auth-login-form />

        <x-slot:footer>
            <p class="auth-card__alt">
                <span>{{ __('auth.login.alt') }}</span>

                <a class="auth-card__alt-link" href="{{ route('register') }}"> {{ __('components.header.signup') }} </a>
            </p>
        </x-slot:footer>
    </x-auth-card>
</x-layouts::app>
