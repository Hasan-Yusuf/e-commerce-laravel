<x-layouts::app :title="__('orders.confirmation.title')">
    @pushOnce('styles')
        @vite('resources/css/features/orders.css')
    @endPushOnce

    <div class="order-confirmation wrapper">
        <x-alert
            type="success"
            :title="__('orders.confirmation.title')"
            :message="__('orders.confirmation.received')"
        />

        <header class="order-confirmation__header">
            <p class="order-confirmation__number">
                {{ __('orders.confirmation.number', ['number' => $order->number]) }}
            </p>

            <p class="order-confirmation__date">
                {{ __('orders.placed_on', ['date' => $order->created_at->translatedFormat('j F Y')]) }}
            </p>

            <x-order-status-badge :status="$order->status" />
        </header>

        {{-- How the order reaches the shopper: a collection is waiting at the
             store, a delivery is estimated. --}}
        <p class="order-confirmation__eta">
            {{
                $order->delivery_method->is(\App\Enums\DeliveryMethod::PICKUP)
                ? __('orders.confirmation.ready_for_pickup')
                : __('orders.confirmation.estimated_delivery')
            }}
        </p>

        <x-order-summary :order="$order" />

        <div class="order-confirmation__actions">
            <x-button :href="route('home')"> {{ __('orders.confirmation.continue_shopping') }} </x-button>

            {{-- Only an account that may actually open the order in its own
                 history is offered it: a guest holds the token and nothing
                 else. --}}
            @can('view', $order)
                <x-button variant="secondary" :href="route('orders.show', $order)">
                    {{ __('orders.confirmation.view_order') }}
                </x-button>
            @endcan
        </div>
    </div>
</x-layouts::app>
