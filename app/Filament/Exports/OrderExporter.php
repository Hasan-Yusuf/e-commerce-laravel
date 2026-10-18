<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class OrderExporter extends Exporter
{
    protected static ?string $model = Order::class;

    #[\Override]
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('number')->label(
                OrderResource::trans('columns.number'),
            ),

            ExportColumn::make('user.name')->label(
                OrderResource::trans('columns.user'),
            ),

            ExportColumn::make('status')->label(
                OrderResource::trans('columns.status'),
            ),

            ExportColumn::make('delivery_method')->label(
                OrderResource::trans('columns.delivery_method'),
            ),

            ExportColumn::make('order_items_count')
                ->counts('orderItems')
                ->label(OrderResource::trans('columns.order_items_count')),

            ExportColumn::make('subtotal_in_fils')
                ->label(OrderResource::trans('columns.subtotal'))
                ->state(static fn (Order $order): float => $order->subtotal_in_fils->toAmount())
                ->prefix('BHD'),

            ExportColumn::make('shipping_amount_in_fils')
                ->label(OrderResource::trans('columns.shipping_amount'))
                ->state(static fn (Order $order): float => $order->shipping_amount_in_fils->toAmount())
                ->prefix('BHD'),

            ExportColumn::make('discount_amount_in_fils')
                ->label(OrderResource::trans('columns.discount_amount'))
                ->state(static fn (Order $order): float => $order->discount_amount_in_fils->toAmount())
                ->prefix('BHD'),

            ExportColumn::make('tax_amount_in_fils')
                ->label(OrderResource::trans('columns.tax_amount'))
                ->state(static fn (Order $order): float => $order->tax_amount_in_fils->toAmount())
                ->prefix('BHD'),

            ExportColumn::make('total_amount_in_fils')
                ->label(OrderResource::trans('columns.total_amount'))
                ->state(static fn (Order $order): float => $order->total_amount_in_fils->toAmount())
                ->prefix('BHD'),

            ExportColumn::make('shippingAddress.address')->label(
                OrderResource::trans('columns.shipping_address'),
            ),

            ExportColumn::make('shippingAddress.country.name')->label(
                OrderResource::trans('columns.country'),
            ),

            ExportColumn::make('notes')->label(
                OrderResource::trans('columns.notes'),
            ),

            ExportColumn::make('paid_at')->label(
                OrderResource::trans('columns.paid_at'),
            ),

            ExportColumn::make('created_at')->label(
                OrderResource::trans('columns.created_at'),
            ),
        ];
    }

    #[\Override]
    public function getFormats(): array
    {
        return [ExportFormat::Csv];
    }

    #[\Override]
    public static function getCompletedNotificationBody(Export $export): string
    {
        $body =
            'Your order export has completed and '
            .(string) Number::format($export->successful_rows)
            .' '
            .(string) str('row')->plural($export->successful_rows)
            .' exported.';

        $failedRowsCount = $export->getFailedRowsCount();

        if ($failedRowsCount > 0) {
            $body .=
                ' '
                .(string) Number::format($failedRowsCount)
                .' '
                .(string) str('row')->plural($failedRowsCount)
                .' failed to export.';
        }

        return $body;
    }
}
