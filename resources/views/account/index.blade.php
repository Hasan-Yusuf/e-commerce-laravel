@php
    $locale = $user->preferredLocale();

    // The locale as the switcher names it, so the page prints a language rather
    // than a code.
    $languageLabel = config("locales.$locale.label", strtoupper($locale));
@endphp

<x-layouts::app :title="__('account.title')">
    @pushOnce('styles')
        @vite([
            'resources/css/features/account.css',
            // The recent orders are listed with the history's own table.
            'resources/css/features/orders.css',
        ])
    @endPushOnce

    <section class="content-section">
        <div class="account-layout wrapper">
            <x-account-nav />

            <div class="account-sections">
                <header class="page-header">
                    <h1 class="page-header__title">{{ __('account.title') }}</h1>

                    <p class="page-header__subtitle">{{ __('account.subtitle') }}</p>
                </header>

                <section class="card account-panel">
                    <h2 class="account-panel__title">{{ __('account.details.title') }}</h2>

                    <dl class="account-details">
                        <div class="account-details__row">
                            <dt>{{ __('account.details.name') }}</dt>
                            <dd>{{ $user->name }}</dd>
                        </div>

                        <div class="account-details__row">
                            <dt>{{ __('account.details.email') }}</dt>
                            <dd>{{ $user->email }}</dd>
                        </div>

                        <div class="account-details__row">
                            <dt>{{ __('account.details.language') }}</dt>
                            <dd>{{ $languageLabel }}</dd>
                        </div>

                        <div class="account-details__row">
                            <dt>{{ __('account.details.member_since') }}</dt>
                            <dd>{{ $user->created_at->translatedFormat('F Y') }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="account-orders">
                    <header class="account-orders__header">
                        <h2 class="account-orders__title">{{ __('account.orders.title') }}</h2>

                        @if ($orders->isNotEmpty())
                            <a class="account-orders__link" href="{{ route('orders.index') }}">
                                {{ __('account.orders.view_all') }}
                            </a>
                        @endif
                    </header>

                    @if ($orders->isEmpty())
                        <x-empty-state
                            icon="cart"
                            :title="__('account.orders.empty.title')"
                            :message="__('account.orders.empty.message')"
                            :action="__('account.orders.empty.action')"
                            :href="route('products.index')"
                        />
                    @else
                        <x-orders-list :orders="$orders" />
                    @endif
                </section>
            </div>
        </div>
    </section>
</x-layouts::app>
