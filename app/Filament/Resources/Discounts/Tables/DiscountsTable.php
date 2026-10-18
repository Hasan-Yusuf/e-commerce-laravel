<?php

declare(strict_types=1);

namespace App\Filament\Resources\Discounts\Tables;

use App\Enums\DiscountType;
use App\Filament\Resources\Discounts\DiscountResource;
use App\Models\Discount;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DiscountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(DiscountResource::trans('columns.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')->label(
                    DiscountResource::trans('columns.type'),
                ),

                TextColumn::make('value')
                    ->label(DiscountResource::trans('columns.value'))
                    ->sortable()
                    ->formatStateUsing(
                        static fn (Discount $record, mixed $state): string => $record->type->formatValue($state),
                    ),

                TextColumn::make('products_count')
                    ->label(DiscountResource::trans('columns.products_count'))
                    ->counts('products')
                    ->default('-'),

                TextColumn::make('categories_count')
                    ->label(DiscountResource::trans('columns.categories_count'))
                    ->counts('categories')
                    ->default('-'),

                TextColumn::make('types_count')
                    ->label(DiscountResource::trans('columns.types_count'))
                    ->counts('productTypes')
                    ->default('-'),

                TextColumn::make('brands_count')
                    ->label(DiscountResource::trans('columns.brands_count'))
                    ->counts('brands')
                    ->default('-'),

                TextColumn::make('starts_at')
                    ->label(DiscountResource::trans('columns.starts_at'))
                    ->dateTime('h:i d/m/Y')
                    ->sinceTooltip()
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label(DiscountResource::trans('columns.ends_at'))
                    ->dateTime('h:i d/m/Y')
                    ->sinceTooltip()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(
                        DiscountResource::trans('columns.is_currently_active'),
                    )
                    ->boolean()
                    ->sortable(
                        query: static fn (
                            Builder $query,
                            string $direction,
                        ) => $query->orderByRaw(
                            'case when activated_at is not null'
                            .' and (starts_at is null or starts_at <= ?)'
                            .' and (ends_at is null or ends_at >= ?)'
                            .' then 1 else 0 end '
                            .($direction === 'desc' ? 'desc' : 'asc'),
                            [now(), now()],
                        ),
                    ),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(DiscountResource::trans('filters.type'))
                    ->options(DiscountType::class),

                SelectFilter::make('is_active')
                    ->label(DiscountResource::trans('filters.is_active'))
                    ->options([
                        1 => DiscountResource::trans('filters.active'),
                        0 => DiscountResource::trans('filters.inactive'),
                    ])
                    ->query(static function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return match (true) {
                            blank($value) => $query,
                            (bool) $value => $query->currentlyActive(),
                            default => $query->whereNot(
                                static fn (Builder $query) => $query->currentlyActive(),
                            ),
                        };
                    }),

                Filter::make('active_now')
                    ->label(DiscountResource::trans('filters.active_now'))
                    ->query(static fn (Builder $query) => $query->currentlyActive()),
            ])
            ->recordActions([
                ActionGroup::make([ViewAction::make(), EditAction::make()]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->emptyStateHeading(DiscountResource::trans('empty_state.heading'))
            ->emptyStateDescription(
                DiscountResource::trans('empty_state.description'),
            )
            ->emptyStateIcon(Heroicon::OutlinedTag)
            ->emptyStateActions([CreateAction::make()]);
    }
}
