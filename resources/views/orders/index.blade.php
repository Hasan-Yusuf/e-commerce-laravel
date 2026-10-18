<x-layouts::app :title="__('orders.title')">
    @pushOnce('styles')
        @vite([
            'resources/css/features/orders.css',
            // The history wears the account area's navigation.
            'resources/css/features/account.css',
        ])
    @endPushOnce

    <section class="content-section">
        <div class="account-layout wrapper">
            <x-account-nav />

            <div class="account-content">
                <header class="orders-header page-header page-header--spaced">
                    <h1 class="page-header__title">{{ __('orders.title') }}</h1>

                    <p class="page-header__subtitle">{{ __('orders.subtitle') }}</p>
                </header>

                @if ($orders->isEmpty())
                    <x-empty-state
                        icon="cart"
                        :title="__('orders.empty.title')"
                        :message="__('orders.empty.message')"
                        :action="__('orders.empty.action')"
                        :href="route('products.index')"
                    />
                @else
                    {{-- How much of the history to show at once is the shopper's own
                         choice; the control keeps the rest of the URL with it. --}}
                    <div class="orders-toolbar">
                        <form class="orders-per-page" method="GET" action="{{ url()->current() }}">
                            <label class="orders-per-page__label" for="orders-per-page">
                                {{ __('orders.index.per_page') }}
                            </label>

                            <select
                                class="orders-per-page__select"
                                id="orders-per-page"
                                name="per_page"
                                data-auto-submit
                            >
                                @foreach (\App\Services\Order\OrderService::PER_PAGE_OPTIONS as $option)
                                    <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                                @endforeach
                            </select>

                            {{-- The button is what submits the choice where the select
                                 cannot do it itself; it stays out of the way where it
                                 can. --}}
                            <x-button variant="bare" class="visually-hidden" type="submit">
                                {{ __('orders.index.per_page_apply') }}
                            </x-button>
                        </form>
                    </div>

                    <x-orders-list :orders="$orders" />

                    {{ $orders->links('pagination::bootstrap-5') }}
                @endif
            </div>
        </div>
    </section>
</x-layouts::app>
