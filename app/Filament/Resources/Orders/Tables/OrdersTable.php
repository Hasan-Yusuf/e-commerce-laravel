<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Exports\OrderExporter;
use App\Filament\Resources\Orders\Actions\CancelOrderAction;
use App\Filament\Resources\Orders\Actions\CollectPaymentOrderAction;
use App\Filament\Resources\Orders\Actions\ConfirmOrderAction;
use App\Filament\Resources\Orders\Actions\FulfilOrderAction;
use App\Filament\Resources\Orders\Actions\RefundOrderAction;
use App\Filament\Resources\Orders\Actions\ReinstateOrderAction;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Filament\Tables\Columns\TextColumn;
use App\Filament\Tables\Filters\DateRangeFilter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->persistSortInSession()
            ->modifyQueryUsing(
                static fn (Builder $query): Builder => $query->with([
                    'user',
                    'shippingAddress.country',
                    'latestPayment',
                ]),
            )
            ->columns([
                TextColumn::make('number')
                    ->label(OrderResource::trans('columns.number'))
                    ->copyable()
                    ->copyMessage(OrderResource::trans('copy_messages.number'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('user.name')->label(
                    OrderResource::trans('columns.user'),
                ),

                TextColumn::make('status')
                    ->label(OrderResource::trans('columns.status'))
                    ->badge(),

                TextColumn::make('delivery_method')
                    ->label(OrderResource::trans('columns.delivery_method'))
                    ->badge()
                    ->toggleable(),

                TextColumn::make('latestPayment.status')
                    ->label(OrderResource::trans('columns.payment_status'))
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('order_items_count')
                    ->counts('orderItems')
                    ->label(OrderResource::trans('columns.order_items_count')),

                TextColumn::make('shippingAddress.address')
                    ->label(OrderResource::trans('columns.shipping_address'))
                    ->wrap()
                    ->searchable(['city', 'state'])
                    ->toggleable(),

                TextColumn::make('shippingAddress.country.name')->label(
                    OrderResource::trans('columns.country'),
                ),

                TextColumn::make('created_at')
                    ->label(OrderResource::trans('columns.created_at'))
                    ->since()
                    ->dateTimeTooltip(config('filament.date_time_format'))
                    ->sortable(),

                MoneyColumn::make('total_amount_in_fils')
                    ->label(OrderResource::trans('columns.total_amount'))
                    ->sortable(
                        query: static fn (
                            Builder $query,
                            string $direction,
                        ) => $query->orderBy(
                            'total_amount_in_fils',
                            $direction,
                        ),
                    ),

                MoneyColumn::make('subtotal_in_fils')
                    ->label(OrderResource::trans('columns.subtotal'))
                    ->sortable(
                        query: static fn (
                            Builder $query,
                            string $direction,
                        ) => $query->orderBy('subtotal_in_fils', $direction),
                    )
                    ->toggleable(),

                MoneyColumn::make('tax_amount_in_fils')
                    ->label(OrderResource::trans('columns.tax_amount'))
                    ->sortable(
                        query: static fn (
                            Builder $query,
                            string $direction,
                        ) => $query->orderBy('tax_amount_in_fils', $direction),
                    )
                    ->toggleable(),

                MoneyColumn::make('shipping_amount_in_fils')
                    ->label(OrderResource::trans('columns.shipping_amount'))
                    ->sortable(
                        query: static fn (
                            Builder $query,
                            string $direction,
                        ) => $query->orderBy(
                            'shipping_amount_in_fils',
                            $direction,
                        ),
                    )
                    ->toggleable(),

                MoneyColumn::make('discount_amount_in_fils')
                    ->label(OrderResource::trans('columns.discount_amount'))
                    ->sortable(
                        query: static fn (
                            Builder $query,
                            string $direction,
                        ) => $query->orderBy(
                            'discount_amount_in_fils',
                            $direction,
                        ),
                    )
                    ->toggleable(),

                TextColumn::make('coupon_code')
                    ->label(OrderResource::trans('columns.coupon_code'))
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('notes')
                    ->label(OrderResource::trans('columns.notes'))
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('paid_at')
                    ->label(OrderResource::trans('columns.paid_at'))
                    ->date('d-m-Y')
                    ->toggleable(),
            ])
            ->groups([
                Group::make('status')
                    ->label(OrderResource::trans('groups.status'))
                    ->collapsible(),

                Group::make(
                    'shippingAddress.country.name_'.app()->getLocale(),
                )
                    ->label(OrderResource::trans('groups.country'))
                    ->collapsible(),
            ])
            ->filters([
                TrashedFilter::make(),

                DateRangeFilter::make('created_at')
                    ->label(OrderResource::trans('filters.created_at'))
                    ->fromLabel(OrderResource::trans('filters.from'))
                    ->untilLabel(OrderResource::trans('filters.until')),

                SelectFilter::make('status')
                    ->label(OrderResource::trans('filters.status'))
                    ->options(OrderStatus::class)
                    ->multiple(),

                SelectFilter::make('delivery_method')
                    ->label(OrderResource::trans('filters.delivery_method'))
                    ->options(DeliveryMethod::class),

                SelectFilter::make('payment_status')
                    ->label(OrderResource::trans('filters.payment_status'))
                    ->options(PaymentStatus::class)
                    ->query(static function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return blank($value)
                            ? $query
                            : $query->whereHas(
                                'latestPayment',
                                static fn (Builder $payment) => $payment->where(
                                    'status',
                                    $value,
                                ),
                            );
                    }),
            ])
            ->headerActions([
                // The export offers a label and a checkbox per column, which one
                // to a row is a column of its own: three abreast reads as a grid
                // of the order's fields rather than a list as long as the export.
                ExportAction::make()
                    ->exporter(OrderExporter::class)
                    ->columnMappingColumns(3),
            ])
            ->recordActions([
                ...self::getRecordActions(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(OrderExporter::class)
                        ->columnMappingColumns(3),
                    RestoreBulkAction::make(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(OrderResource::trans('empty_state.heading'))
            ->emptyStateDescription(
                OrderResource::trans('empty_state.description'),
            )
            ->emptyStateIcon(Heroicon::OutlinedShoppingCart);
    }

    /**
     * @return array<int, Action|ActionGroup>
     */
    public static function getRecordActions(): array
    {
        return [
            FulfilOrderAction::make(),
            CancelOrderAction::make(),
            RefundOrderAction::make(),
            ConfirmOrderAction::make(),
            ReinstateOrderAction::make(),
            ActionGroup::make([
                CollectPaymentOrderAction::make(),
                ViewAction::make(),
                EditAction::make(),
            ]),
        ];
    }
}
