<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Services\Catalog\ProductPricingService;
use App\Support\Money;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class ProductExporter extends Exporter
{
    protected static ?string $model = Product::class;

    #[\Override]
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label(
                ProductResource::trans('columns.name'),
            ),

            ExportColumn::make('price_in_fils')
                ->label(ProductResource::trans('columns.price'))
                ->state(static fn (Product $product): float => $product->price_in_fils->toAmount())
                ->prefix('BHD'),

            ExportColumn::make('discount_price')
                ->label(ProductResource::trans('columns.discount_price'))
                ->state(static function (Product $product): string|float {
                    $pricingService = app(ProductPricingService::class);
                    $priceInFils = $pricingService->getCurrentPrice($product);

                    // The storefront's own pricing decides this column, so the
                    // export cannot disagree with what a shopper pays.
                    if ($priceInFils >= $product->price_in_fils->toFils()) {
                        return '-';
                    }

                    return Money::fromFils($priceInFils)->toAmount();
                })
                ->formatStateUsing(
                    static fn (mixed $state): string => is_numeric($state) ? "BHD {$state}" : '-',
                ),

            ExportColumn::make('cost_price_in_fils')
                ->label(ProductResource::trans('columns.cost_price'))
                ->state(static fn (Product $product): float => $product->cost_price_in_fils->toAmount())
                ->prefix('BHD'),

            ExportColumn::make('type.name')->label(
                ProductResource::trans('columns.type_name'),
            ),

            ExportColumn::make('type.category.name')->label(
                ProductResource::trans('columns.category_name'),
            ),

            ExportColumn::make('featured_at')->label(
                ProductResource::trans('fields.featured_at'),
            ),

            ExportColumn::make('activated_at')->label(
                ProductResource::trans('fields.activated_at'),
            ),

            ExportColumn::make('stock')->label(
                ProductResource::trans('fields.stock'),
            ),

            ExportColumn::make('reserved_stock')->label(
                ProductResource::trans('fields.reserved_stock'),
            ),

            ExportColumn::make('available_stock')
                ->label(ProductResource::trans('fields.available_stock'))
                ->formatStateUsing(
                    static fn (Product $record) => $record->getAvailableStock(),
                ),
        ];
    }

    #[\Override]
    public static function getCompletedNotificationBody(Export $export): string
    {
        $body =
            'Your product export has completed and '
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

    #[\Override]
    public function getFileName(Export $export): string
    {
        return "Products_{$export->getKey()}.xlsx";
    }

    #[\Override]
    public function getFormats(): array
    {
        return [ExportFormat::Csv];
    }
}
