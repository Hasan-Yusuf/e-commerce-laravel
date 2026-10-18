<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\Actions\FulfilOrderAction;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\Order;
use App\Services\DashboardService;
use App\Support\HasTranslationKeys;
use App\Support\TranslationKey;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Support\Htmlable;

/**
 * The pending and processing orders, oldest first, with a Fulfil action on each
 * row so the work the dashboard surfaces can be finished without leaving it.
 */
class OrdersRequiringAttention extends TableWidget
{
    use HasTranslationKeys;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getTableHeading(): string|Htmlable|null
    {
        return self::trans('tables.orders_requiring_attention');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(app(DashboardService::class)->ordersRequiringAttentionQuery())
            ->paginated(false)
            ->columns([
                TextColumn::make('number')
                    ->label(self::trans('columns.number')),

                TextColumn::make('user.name')
                    ->label(self::trans('columns.customer')),

                MoneyColumn::make('total_amount_in_fils')
                    ->label(self::trans('columns.total')),

                TextColumn::make('created_at')
                    ->label(self::trans('columns.placed'))
                    ->since(),
            ])
            ->recordUrl(
                static fn (Order $record): string => OrderResource::getUrl('view', [
                    'record' => $record,
                ]),
            )
            ->recordActions([
                FulfilOrderAction::make(),
            ]);
    }

    protected static function translationKeys(): TranslationKey
    {
        return TranslationKey::for(static::class)->forFilament()->setKey('dashboard');
    }
}
