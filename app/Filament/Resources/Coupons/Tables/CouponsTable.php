<?php

declare(strict_types=1);

namespace App\Filament\Resources\Coupons\Tables;

use App\Enums\DiscountType;
use App\Filament\Resources\Coupons\CouponResource;
use App\Models\Coupon;
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

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(CouponResource::trans('columns.code'))
                    ->copyable()
                    ->copyMessage(CouponResource::trans('copy_messages.code'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('discount_type')->label(
                    CouponResource::trans('columns.type'),
                ),

                TextColumn::make('discount_value')
                    ->label(CouponResource::trans('columns.value'))
                    ->sortable(
                        query: static fn (Builder $query, string $direction): Builder => $query->orderBy(
                            'discount_value_in_fils',
                            $direction,
                        ),
                    )
                    ->formatStateUsing(
                        static fn (Coupon $record, mixed $state): string => $record->discount_type->formatValue($state),
                    ),

                TextColumn::make('used_count')
                    ->label(CouponResource::trans('columns.used_count'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('usage_limit')
                    ->label(CouponResource::trans('columns.usage_limit'))
                    ->badge()
                    ->placeholder(
                        CouponResource::trans('columns.unlimited'),
                    ),

                TextColumn::make('activated_at')
                    ->label(CouponResource::trans('columns.activated_at'))
                    ->dateTime('h:i d/m/Y')
                    ->sinceTooltip()
                    ->sortable(),

                TextColumn::make('starts_at')
                    ->label(CouponResource::trans('columns.starts_at'))
                    ->dateTime('h:i d/m/Y')
                    ->sinceTooltip()
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label(CouponResource::trans('columns.expires_at'))
                    ->dateTime('h:i d/m/Y')
                    ->sinceTooltip()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(
                        CouponResource::trans('columns.is_currently_active'),
                    )
                    ->boolean()
                    ->sortable(
                        query: static fn (
                            Builder $query,
                            string $direction,
                        ) => $query->orderByRaw(
                            'case when activated_at is not null'
                            .' and (starts_at is null or starts_at <= ?)'
                            .' and (expires_at is null or expires_at >= ?)'
                            .' then 1 else 0 end '
                            .($direction === 'desc' ? 'desc' : 'asc'),
                            [now(), now()],
                        ),
                    ),
            ])
            ->filters([
                SelectFilter::make('discount_type')
                    ->label(CouponResource::trans('filters.type'))
                    ->options(DiscountType::class),

                SelectFilter::make('is_active')
                    ->label(CouponResource::trans('filters.is_active'))
                    ->options([
                        1 => CouponResource::trans('filters.active'),
                        0 => CouponResource::trans('filters.inactive'),
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
                    ->label(CouponResource::trans('filters.active_now'))
                    ->query(static fn (Builder $query) => $query->currentlyActive()),
            ])
            ->recordActions([
                ActionGroup::make([ViewAction::make(), EditAction::make()]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->emptyStateHeading(CouponResource::trans('empty_state.heading'))
            ->emptyStateDescription(
                CouponResource::trans('empty_state.description'),
            )
            ->emptyStateIcon(Heroicon::OutlinedTicket)
            ->emptyStateActions([CreateAction::make()]);
    }
}
