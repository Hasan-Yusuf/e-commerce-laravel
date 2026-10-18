<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\Actions\RestockProductAction;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Services\DashboardService;
use App\Support\HasTranslationKeys;
use App\Support\TranslationKey;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Active products at or below their restock threshold, each with a Restock
 * action so stock can be topped up from the dashboard.
 */
class LowStockProducts extends TableWidget
{
    use HasTranslationKeys;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected function getTableHeading(): string|Htmlable|null
    {
        return self::trans('tables.low_stock_products');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(app(DashboardService::class)->lowStockProductsQuery())
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->label(self::trans('columns.product_name')),

                TextColumn::make('sku')
                    ->label(self::trans('columns.sku')),

                TextColumn::make('available_stock')
                    ->label(self::trans('columns.available'))
                    ->numeric(),

                TextColumn::make('low_stock_threshold')
                    ->label(self::trans('columns.threshold'))
                    ->numeric(),
            ])
            ->recordUrl(
                static fn (Product $record): string => ProductResource::getUrl('edit', [
                    'record' => $record,
                ]),
            )
            ->recordActions([
                RestockProductAction::make(),
            ]);
    }

    protected static function translationKeys(): TranslationKey
    {
        return TranslationKey::for(static::class)->forFilament()->setKey('dashboard');
    }
}
