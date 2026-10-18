<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\SemanticColor;
use App\Filament\Infolists\Components\MoneyEntry;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\OrderItem;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            RepeatableEntry::make('orderItems')
                ->label(OrderResource::trans('info.order_items'))
                ->columnSpanFull()
                ->table([
                    TableColumn::make(
                        OrderResource::trans('info.product_name'),
                    ),

                    TableColumn::make(OrderResource::trans('info.product_sku')),

                    TableColumn::make(OrderResource::trans('info.quantity')),

                    TableColumn::make(OrderResource::trans('info.unit_price')),

                    TableColumn::make(OrderResource::trans('info.total_price')),
                ])
                ->schema([
                    TextEntry::make('product_name')
                        ->words(5)
                        ->url(static function (OrderItem $record): ?string {
                            $productId = $record->product?->id;

                            return
                                $productId === null
                                    ? null
                                    : ProductResource::getUrl('edit', ['record' => $productId]);
                        })
                        ->tooltip('Go to the product page'),

                    TextEntry::make('sku'),

                    TextEntry::make('quantity'),

                    MoneyEntry::make('unit_price_in_fils'),

                    MoneyEntry::make('total_price_in_fils'),
                ]),

            Group::make([
                Section::make(OrderResource::trans('sections.customer'))
                    ->icon(Heroicon::UserCircle)
                    ->iconColor(SemanticColor::Primary->value)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name')->label(
                            OrderResource::trans('info.customer_name'),
                        ),

                        TextEntry::make('user.status')
                            ->label(
                                OrderResource::trans('info.customer_status'),
                            )
                            ->badge(),

                        TextEntry::make('shippingAddress.address')
                            ->label(
                                OrderResource::trans(
                                    'info.customer_shipping_address',
                                ),
                            )
                            ->copyable(),

                        TextEntry::make('billingAddress.address')
                            ->label(
                                OrderResource::trans(
                                    'info.customer_billing_address',
                                ),
                            )
                            ->copyable(),

                        TextEntry::make('shippingAddress.country.name')
                            ->label(OrderResource::trans('info.country'))
                            ->icon(Heroicon::MapPin)
                            ->iconColor(SemanticColor::Primary->value),

                        TextEntry::make('user.email')
                            ->label(OrderResource::trans('info.customer_email'))
                            ->copyable()
                            ->icon(Heroicon::Envelope)
                            ->iconColor(SemanticColor::Primary->value),

                        TextEntry::make('user.phone.full_phone_number')
                            ->label(OrderResource::trans('info.phone_number'))
                            ->icon(Heroicon::Phone)
                            ->iconColor(SemanticColor::Primary->value),
                    ]),
            ]),

            Section::make(OrderResource::trans('sections.order'))
                ->icon(Heroicon::ShoppingBag)
                ->iconColor(SemanticColor::Primary->value)
                ->columns(3)
                ->schema([
                    TextEntry::make('number')
                        ->label(OrderResource::trans('info.order_number'))
                        ->weight(FontWeight::Bold)
                        ->copyable(),

                    TextEntry::make('coupon_code')
                        ->label(OrderResource::trans('info.coupon_code'))
                        ->placeholder('-'),

                    TextEntry::make('status')
                        ->label(OrderResource::trans('info.status'))
                        ->badge(),

                    TextEntry::make('created_at')
                        ->label(OrderResource::trans('info.created_at'))
                        ->since()
                        ->dateTimeTooltip(config('filament.date_time_format')),

                    Grid::make(4)
                        ->schema([
                            MoneyEntry::make('subtotal_in_fils')
                                ->label(OrderResource::trans('info.subtotal')),

                            MoneyEntry::make('tax_amount_in_fils')
                                ->label(OrderResource::trans('info.tax')),

                            MoneyEntry::make('discount_amount_in_fils')
                                ->label(OrderResource::trans('info.discount'))
                                ->color(SemanticColor::Danger->value),

                            MoneyEntry::make('total_amount_in_fils')
                                ->label(OrderResource::trans('info.total'))
                                ->weight('bold')
                                ->color(SemanticColor::Success->value),
                        ])
                        ->columnSpanFull(),
                ]),

            Section::make(OrderResource::trans('sections.payments'))
                ->columnSpanFull()
                ->icon(Heroicon::Banknotes)
                ->iconColor(SemanticColor::Primary->value)
                ->schema([
                    RepeatableEntry::make('payments')
                        ->hiddenLabel()
                        ->table([
                            TableColumn::make(
                                OrderResource::trans('info.payment_method'),
                            ),

                            TableColumn::make(
                                OrderResource::trans('info.payment_status'),
                            ),

                            TableColumn::make(
                                OrderResource::trans('info.payment_amount'),
                            ),

                            TableColumn::make(
                                OrderResource::trans('info.payment_paid_at'),
                            ),
                        ])
                        ->schema([
                            TextEntry::make('method')->badge(),

                            TextEntry::make('status')->badge(),

                            MoneyEntry::make('amount_in_fils'),

                            TextEntry::make('paid_at')
                                ->dateTime(config('filament.date_time_format'))
                                ->placeholder('-'),
                        ]),
                ]),
        ]);
    }
}
