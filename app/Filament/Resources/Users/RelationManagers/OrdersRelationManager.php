<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

/**
 * The orders a customer has placed, surfaced on their view page. Read-only:
 * orders are managed from the Orders resource.
 */
class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Orders';

    #[\Override]
    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordTitleAttribute('number')
            ->recordUrl(
                static fn ($record): string => OrderResource::getUrl('view', [
                    'record' => $record,
                ]),
            )
            ->columns([
                TextColumn::make('number')
                    ->label(OrderResource::trans('columns.number'))
                    ->sortable(),

                TextColumn::make('status')
                    ->label(OrderResource::trans('columns.status'))
                    ->badge(),

                MoneyColumn::make('total_amount_in_fils')
                    ->label(OrderResource::trans('columns.total_amount'))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(OrderResource::trans('columns.created_at'))
                    ->since()
                    ->dateTimeTooltip(config('filament.date_time_format'))
                    ->sortable(),
            ]);
    }
}
