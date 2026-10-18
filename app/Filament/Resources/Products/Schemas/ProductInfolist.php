<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\SemanticColor;
use App\Enums\StockStatus;
use App\Enums\Unit;
use App\Filament\Infolists\Components\MoneyEntry;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(ProductResource::trans('sections.main_info'))
                ->icon(Heroicon::OutlinedCube)
                ->iconColor(SemanticColor::Primary->value)
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('name')
                        ->label(ProductResource::trans('infolists.name'))
                        ->weight(FontWeight::Bold)
                        ->copyable(),

                    TextEntry::make('sku')
                        ->label(ProductResource::trans('infolists.sku'))
                        ->copyable(),

                    TextEntry::make('type.name')
                        ->label(ProductResource::trans('infolists.type'))
                        ->icon(Heroicon::OutlinedTag)
                        ->iconColor(SemanticColor::Primary->value)
                        ->placeholder('-'),

                    TextEntry::make('weight')
                        ->label(ProductResource::trans('infolists.weight'))
                        ->numeric()
                        ->prefix(Unit::WEIGHT->prefix())
                        ->suffix(Unit::WEIGHT->suffix())
                        ->placeholder('-'),

                    TextEntry::make('specs')
                        ->label(ProductResource::trans('infolists.specs'))
                        ->columnSpanFull()
                        ->inlineLabel()
                        ->badge()
                        ->placeholder('-'),
                ]),

            Section::make(ProductResource::trans('sections.finance_and_stock'))
                ->icon(Heroicon::OutlinedBanknotes)
                ->iconColor(SemanticColor::Primary->value)
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    MoneyEntry::make('price_in_fils')
                        ->label(ProductResource::trans('infolists.price'))
                        ->weight(FontWeight::Bold)
                        ->icon(Heroicon::OutlinedCurrencyDollar)
                        ->iconColor(SemanticColor::Primary->value),

                    MoneyEntry::make('cost_price_in_fils')
                        ->label(ProductResource::trans('infolists.cost_price'))
                        ->placeholder('-')
                        ->icon(Heroicon::OutlinedCurrencyDollar)
                        ->iconColor(SemanticColor::Warning->value),

                    TextEntry::make('stock_status')
                        ->label(ProductResource::trans('columns.stock_status'))
                        ->state(
                            static fn (Product $record): StockStatus => $record
                                ->stockStatus(),
                        )
                        ->badge(),

                    Grid::make(3)
                        ->columnSpanFull()
                        ->schema([
                            TextEntry::make('stock')
                                ->label(
                                    ProductResource::trans('infolists.stock').' +',
                                )
                                ->numeric()
                                ->icon(Heroicon::OutlinedArchiveBox)
                                ->color(
                                    static fn (
                                        Product $record,
                                    ): string => $record->isLowOnStock()
                                        ? SemanticColor::Warning->value
                                        : SemanticColor::Success->value,
                                ),

                            TextEntry::make('reserved_stock')
                                ->label(
                                    ProductResource::trans(
                                        'infolists.reserved_stock',
                                    )
                                        .' -',
                                )
                                ->numeric()
                                ->icon(Heroicon::OutlinedArchiveBoxArrowDown)
                                ->color(
                                    static fn (
                                        Product $record,
                                    ): string => $record->isLowOnStock()
                                        ? SemanticColor::Danger->value
                                        : SemanticColor::Warning->value,
                                ),

                            TextEntry::make('available_stock')
                                ->label(
                                    ProductResource::trans(
                                        'infolists.available_stock',
                                    ),
                                )
                                ->numeric()
                                ->state(
                                    static fn (Product $record): int => $record
                                        ->getAvailableStock(),
                                )
                                ->color(
                                    static fn (
                                        Product $record,
                                    ): string => $record->isLowOnStock()
                                        ? SemanticColor::Danger->value
                                        : SemanticColor::Success->value,
                                )
                                ->icon(
                                    static fn (
                                        Product $record,
                                    ) => $record->isLowOnStock()
                                        ? Heroicon::OutlinedShieldExclamation
                                        : Heroicon::OutlinedCheckBadge,
                                )
                                ->iconColor(
                                    static fn (
                                        Product $record,
                                    ): string => $record->isLowOnStock()
                                        ? SemanticColor::Danger->value
                                        : SemanticColor::Success->value,
                                ),
                        ]),
                ]),
        ]);
    }
}
