@props([
    'variant' => 'bar',
])

@php
    $isDrawer = $variant === 'drawer';
    $user = auth()->user();
@endphp

<div class="{{ $isDrawer ? 'site-header__drawer-actions' : 'site-header__actions' }}">
    {{-- Preferences: language toggle (currency chip re-added later). --}}
    <div class="site-header__group">
        <form class="site-header__lang-form" method="POST" action="{{ route('lang') }}">
            @csrf
            <x-button
                variant="secondary"
                class="site-header__action"
                type="submit"
                name="language"
                :value="app()->getLocale() === 'en' ? 'ar' : 'en'"
            >
                <x-icons.globe-icon class="site-header__action-icon" aria-hidden="true" />

                {{-- The control says what it is as well as what it shows: the
                     locale code stays visible, and the accessible name repeats
                     it, so what a voice-control user says is what they see. --}}
                <span class="visually-hidden"> {{ __('components.header.language') }}: </span>
                <span class="site-header__action-label"> {{ strtoupper(app()->getLocale()) }} </span>
            </x-button>
        </form>
    </div>

    @auth
        <div class="site-header__group">
            {{-- The account area is where the shopper's own things are, so the
                 chip they see reports who is signed in and opens it. --}}
            <x-button
                variant="secondary"
                class="site-header__action site-header__account"
                :href="route('account.index')"
                :title="$user->name"
            >
                <x-icons.user-icon class="site-header__action-icon" aria-hidden="true" />
                <span class="site-header__action-label"> {{ __('components.header.account') }} </span>
            </x-button>

            <form class="site-header__logout-form" method="POST" action="{{ route('logout') }}">
                @csrf

                <x-button variant="secondary" class="site-header__action site-header__logout" type="submit">
                    <x-icons.logout-icon class="site-header__action-icon" aria-hidden="true" />
                    <span class="site-header__action-label"> {{ __('components.header.logout') }} </span>
                </x-button>
            </form>
        </div>
    @else
        {{-- A real link to the sign-in page, upgraded to the login/register
             dialog by the auth-modal script when JavaScript is available. --}}
        <x-button
            variant="secondary"
            class="site-header__action site-header__account"
            :href="route('login')"
            data-auth-open
            aria-haspopup="dialog"
            aria-controls="auth-modal"
            aria-expanded="false"
        >
            <x-icons.user-icon class="site-header__action-icon" aria-hidden="true" />
            <span class="site-header__action-label"> {{ __('components.header.login') }} </span>
        </x-button>
    @endauth

    {{-- Cart stays in the top bar on every screen size. The wishlist has no
         top-bar shortcut of its own: it lives under the cart. --}}
    @unless ($isDrawer)
        <x-button
            variant="secondary"
            class="site-header__action site-header__cart"
            :href="Route::has('cart.index') ? route('cart.index') : '#'"
            :aria-label="__('components.header.cart')"
        >
            <x-icons.cart-icon class="site-header__action-icon" aria-hidden="true" />

            <livewire:cart-badge />

            <span class="site-header__action-label"> {{ __('components.header.cart') }} </span>
        </x-button>
    @endunless
</div>
