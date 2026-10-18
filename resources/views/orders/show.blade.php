<x-layouts::app :title="__('orders.show.title', ['number' => $order->number])">
    @pushOnce('styles')
        @vite([
            'resources/css/features/orders.css',
            // One order is read inside the account area, navigation and all.
            'resources/css/features/account.css',
        ])
    @endPushOnce

    <section class="content-section">
        <div class="account-layout wrapper">
            <x-account-nav />

            <div class="account-content order-detail">
                <header class="order-detail__header">
                    <h1 class="page-header__title">{{ __('orders.show.title', ['number' => $order->number]) }}</h1>

                    <p class="order-detail__date">
                        {{ __('orders.placed_on', ['date' => $order->created_at->translatedFormat('j F Y')]) }}
                    </p>

                    <x-order-status-badge :status="$order->status" />
                </header>

                <x-order-summary :order="$order" />

                <div class="order-detail__actions">
                    <x-button variant="secondary" :href="route('orders.index')"> {{ __('orders.show.back') }} </x-button>
                </div>
            </div>
        </div>
    </section>
</x-layouts::app>
