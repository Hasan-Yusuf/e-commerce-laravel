<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

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
 * The newest orders at a glance, whatever their status, so the dashboard still
 * shows what just happened when nothing needs attention.
 */
class RecentOrders extends TableWidget
{
    use HasTranslationKeys;

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected function getTableHeading(): string|Htmlable|null
    {
        return self::trans('tables.recent_orders');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(app(DashboardService::class)->recentOrdersQuery())
            ->paginated(false)
            ->columns([
                TextColumn::make('number')
                    ->label(self::trans('columns.number')),

                TextColumn::make('user.name')
                    ->label(self::trans('columns.customer')),

                TextColumn::make('status')
                    ->label(self::trans('columns.status'))
                    ->badge(),

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
            );
    }

    protected static function translationKeys(): TranslationKey
    {
        return TranslationKey::for(static::class)->forFilament()->setKey('dashboard');
    }
}
