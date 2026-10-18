<?php

declare(strict_types=1);

namespace App\Filament\Resources\Coupons\Schemas;

use App\Enums\SemanticColor;
use App\Filament\Infolists\Components\MoneyEntry;
use App\Filament\Resources\Coupons\CouponResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Coupon;
use App\Support\Money;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class CouponInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(CouponResource::trans('sections.general_information'))
                ->icon(Heroicon::OutlinedTicket)
                ->iconColor(SemanticColor::Primary->value)
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('code')
                        ->label(CouponResource::trans('infolists.code'))
                        ->weight(FontWeight::Bold)
                        ->copyable(),

                    TextEntry::make('discount_type')
                        ->label(CouponResource::trans('infolists.type'))
                        ->badge(),

                    TextEntry::make('is_active')
                        ->label(
                            CouponResource::trans('columns.is_currently_active'),
                        )
                        ->badge()
                        ->formatStateUsing(
                            static fn (mixed $state): string => (bool) $state
                                ? CouponResource::trans('filters.active')
                                : CouponResource::trans('filters.inactive'),
                        )
                        ->color(
                            static fn (mixed $state): string => (bool) $state
                                ? SemanticColor::Success->value
                                : SemanticColor::Danger->value,
                        ),

                    TextEntry::make('description')
                        ->label(CouponResource::trans('infolists.description'))
                        ->placeholder('-')
                        ->columnSpanFull(),

                    TextEntry::make('discount_value')
                        ->label(CouponResource::trans('infolists.value'))
                        ->weight(FontWeight::Bold)
                        ->color(SemanticColor::Danger->value)
                        ->formatStateUsing(
                            static fn (Coupon $record, mixed $state): string => $record->discount_type->formatValue(
                                $state,
                            ),
                        ),

                    TextEntry::make('maximum_discount')
                        ->label(CouponResource::trans('infolists.maximum_discount'))
                        ->color(SemanticColor::Warning->value)
                        ->money('BHD')
                        ->placeholder(CouponResource::trans('infolists.unlimited')),

                    TextEntry::make('min_order_amount')
                        ->label(CouponResource::trans('infolists.min_order_amount'))
                        ->color(SemanticColor::Info->value)
                        ->money('BHD')
                        ->state(
                            static fn (Coupon $record): ?float => self::amountOrNull(
                                $record->min_order_amount,
                            ),
                        )
                        ->placeholder('-'),
                ]),

            Section::make(CouponResource::trans('sections.validity'))
                ->icon(Heroicon::OutlinedCalendarDays)
                ->iconColor(SemanticColor::Primary->value)
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('activated_at')
                        ->label(CouponResource::trans('infolists.activated_at'))
                        ->since()
                        ->dateTimeTooltip(config('filament.date_time_format'))
                        ->placeholder('-'),

                    TextEntry::make('starts_at')
                        ->label(CouponResource::trans('infolists.starts_at'))
                        ->since()
                        ->dateTimeTooltip(config('filament.date_time_format'))
                        ->placeholder('-'),

                    TextEntry::make('expires_at')
                        ->label(CouponResource::trans('infolists.expires_at'))
                        ->since()
                        ->dateTimeTooltip(config('filament.date_time_format'))
                        ->placeholder('-'),
                ]),

            Section::make(CouponResource::trans('sections.usage'))
                ->icon(Heroicon::OutlinedChartBarSquare)
                ->iconColor(SemanticColor::Primary->value)
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('usage_limit')
                        ->label(CouponResource::trans('infolists.usage_limit'))
                        ->placeholder(CouponResource::trans('infolists.unlimited')),

                    TextEntry::make('used_count')
                        ->label(CouponResource::trans('infolists.used_count'))
                        ->badge()
                        ->color(SemanticColor::Primary->value),

                    TextEntry::make('orders_count')
                        ->label(CouponResource::trans('infolists.orders_count'))
                        ->state(
                            static fn (Coupon $record): int => $record
                                ->orders()
                                ->count(),
                        ),

                    TextEntry::make('total_discount')
                        ->label(CouponResource::trans('infolists.total_discount'))
                        ->color(SemanticColor::Success->value)
                        ->money('BHD')
                        ->state(
                            static fn (Coupon $record): ?float => self::amountOrNull(
                                Money::fromFils(
                                    (int) $record->orders()->sum(
                                        'discount_amount_in_fils',
                                    ),
                                )->toAmount(),
                            ),
                        ),
                ]),

            Section::make(CouponResource::trans('sections.used_orders'))
                ->icon(Heroicon::OutlinedShoppingBag)
                ->iconColor(SemanticColor::Primary->value)
                ->columnSpanFull()
                ->schema([
                    RepeatableEntry::make('orders')
                        ->hiddenLabel()
                        ->table([
                            TableColumn::make(
                                OrderResource::trans('info.order_number'),
                            ),

                            TableColumn::make(
                                OrderResource::trans('info.customer_name'),
                            ),

                            TableColumn::make(
                                OrderResource::trans('info.discount'),
                            ),
                        ])
                        ->schema([
                            TextEntry::make('number')->copyable(),

                            TextEntry::make('user.name'),

                            MoneyEntry::make('discount_amount_in_fils')
                                ->color(SemanticColor::Danger->value),
                        ]),
                ]),
        ]);
    }

    /**
     * A money value that reads only when there is one: a coupon with no minimum
     * or no orders using it shows a dash rather than a row of zeroes.
     */
    private static function amountOrNull(mixed $amount): ?float
    {
        $amount = (float) $amount;

        return $amount === 0.0 ? null : $amount;
    }
}
