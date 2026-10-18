<x-layouts::app :title="__('auth.register.title')">
    @pushOnce('styles')
        @vite('resources/css/features/auth.css')
    @endPushOnce

    <x-auth-card class="grid-center" :title="__('auth.register.title')" :subtitle="__('auth.register.subtitle')">
        {{-- The dialog renders this same form (auth-register-form.blade.php). --}}
        <x-auth-register-form />

        <x-slot:footer>
            <p class="auth-card__alt">
                <span>{{ __('auth.register.alt') }}</span>

                <a class="auth-card__alt-link" href="{{ route('login') }}"> {{ __('components.header.login') }} </a>
            </p>
        </x-slot:footer>
    </x-auth-card>
</x-layouts::app>
