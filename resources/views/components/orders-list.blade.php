@props([
    // The orders to list, newest first: the account's whole history, or the few
    // most recent of it on the overview. Both read a row the same way.
    'orders',
])

{{-- The orders: one row of columns while the column titles fit, and one card per
     order when they do not — the same shape the cart's lines take, and the same
     rules (see orders.css). The wrapper is the query container; the list inside
     it is the grid, so the layout can answer for the room it is actually given. --}}
<div class="orders-list">
    <div class="orders-list__items">
        {{-- Column names, shown only where the rows lay out as columns. Every
             cell names itself for screen readers, so this row is decorative. --}}
        <div class="orders-list__header" aria-hidden="true">
            <span class="orders-list__header-cell"> {{ __('orders.index.number') }} </span>
            <span class="orders-list__header-cell"> {{ __('orders.index.date') }} </span>
            <span class="orders-list__header-cell"> {{ __('orders.index.status') }} </span>
            <span class="orders-list__header-cell"> {{ __('orders.index.payment') }} </span>
            <span class="orders-list__header-cell orders-list__header-cell--end"> {{ __('orders.index.total') }} </span>
            <span class="orders-list__header-cell orders-list__header-cell--end">
                {{ __('orders.index.action') }}
            </span>
        </div>

        @foreach ($orders as $order)
            <article class="order-row">
                <span class="order-row__number">{{ $order->number }}</span>

                <div class="order-row__meta">
                    <span class="order-row__field order-row__field--date">
                        <span class="order-row__label | visually-hidden"> {{ __('orders.index.date') }} </span>
                        {{ $order->created_at->translatedFormat('j F Y') }}
                    </span>

                    {{-- The method the order was placed with: a payment row is
                         written as the order is placed, so the first of them is
                         that method. --}}
                    <span class="order-row__field order-row__field--payment">
                        <span class="order-row__label | visually-hidden"> {{ __('orders.index.payment') }} </span>
                        {{ $order->payments->first()?->method->getLabel() }}
                    </span>
                </div>

                <span class="order-row__status">
                    <x-order-status-badge :status="$order->status" />
                </span>

                <span class="order-row__total">
                    <span class="order-row__label | visually-hidden"> {{ __('orders.index.total') }} </span>
                    <span class="order-row__total-value"> {{ $order->total_amount_in_fils }} </span>
                </span>

                <span class="order-row__action">
                    <a class="order-row__link" href="{{ route('orders.show', $order) }}">
                        {{ __('orders.index.view') }}
                    </a>
                </span>
            </article>
        @endforeach
    </div>
</div>
