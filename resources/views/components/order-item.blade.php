@props([
    // The order line, as it was snapshotted when the order was placed.
    'item',
])

<li class="line-item">
    <div class="line-item__info">
        <span class="line-item__name">{{ $item->product_name }}</span>

        @if ($item->sku)
            <span class="line-item__sku"> {{ __('orders.item.sku', ['sku' => $item->sku]) }} </span>
        @endif

        <span class="line-item__quantity">
            {{ __('orders.item.quantity', ['count' => $item->quantity]) }} × {{ $item->unit_price_in_fils }}
        </span>
    </div>

    <span class="line-item__total"> {{ $item->total_price_in_fils }} </span>
</li>
