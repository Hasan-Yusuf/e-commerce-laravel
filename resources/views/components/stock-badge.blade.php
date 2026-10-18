@props([
    'product',
])

@php
    $status = $product->stockStatus();
    $availableStock = $product->getAvailableStock();

    // The stock state in the storefront's status colours, as the shared `.badge`
    // modifier that carries the colour.
    $modifier = match ($status) {
        \App\Enums\StockStatus::OUT_OF_STOCK => 'danger',
        \App\Enums\StockStatus::LOW_STOCK => 'warning',
        default => 'success',
    };
@endphp

<span class="badge badge--{{ $modifier }}">
    @if ($status === \App\Enums\StockStatus::LOW_STOCK)
        {{ trans_choice('catalog.stock.low_stock', $availableStock, ['count' => $availableStock]) }}
    @else
        {{ $status->getLabel() }}
    @endif
</span>
