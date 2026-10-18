<?php

declare(strict_types=1);

namespace App\Filament\Resources\Countries\Tables;

use App\Filament\Resources\Countries\CountryResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CountriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(CountryResource::trans('columns.name'))
                    ->searchable(['name_en', 'name_ar'])
                    ->sortable(
                        query: static fn (
                            Builder $query,
                            string $direction,
                        ) => $query->orderBy('name_en', $direction),
                    ),

                TextColumn::make('calling_code')
                    ->label(CountryResource::trans('columns.calling_code'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('orders_count')
                    ->label(CountryResource::trans('columns.orders_count'))
                    ->counts('orders'),
            ])
            ->filters([])
            ->recordActions([
                ActionGroup::make([DeleteAction::make(), EditAction::make()]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->emptyStateHeading(CountryResource::trans('empty_state.heading'))
            ->emptyStateDescription(
                CountryResource::trans('empty_state.description'),
            )
            ->emptyStateIcon(Heroicon::OutlinedGlobeAmericas)
            ->emptyStateActions([CreateAction::make()]);
    }
}
