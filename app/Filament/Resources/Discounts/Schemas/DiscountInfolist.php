<?php

declare(strict_types=1);

namespace App\Filament\Resources\Discounts\Schemas;

use App\Enums\SemanticColor;
use App\Filament\Resources\Discounts\DiscountResource;
use App\Models\Discount;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class DiscountInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(DiscountResource::trans('sections.general_information'))
                ->icon(Heroicon::OutlinedReceiptPercent)
                ->iconColor(SemanticColor::Primary->value)
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('name')
                        ->label(DiscountResource::trans('infolists.name'))
                        ->weight(FontWeight::Bold),

                    TextEntry::make('type')
                        ->label(DiscountResource::trans('infolists.type'))
                        ->badge(),

                    TextEntry::make('is_active')
                        ->label(
                            DiscountResource::trans('columns.is_currently_active'),
                        )
                        ->badge()
                        ->formatStateUsing(
                            static fn (mixed $state): string => (bool) $state
                                ? DiscountResource::trans('filters.active')
                                : DiscountResource::trans('filters.inactive'),
                        )
                        ->color(
                            static fn (mixed $state): string => (bool) $state
                                ? SemanticColor::Success->value
                                : SemanticColor::Danger->value,
                        ),

                    TextEntry::make('value')
                        ->label(DiscountResource::trans('infolists.value'))
                        ->weight(FontWeight::Bold)
                        ->color(SemanticColor::Danger->value)
                        ->formatStateUsing(
                            static fn (Discount $record, mixed $state): string => $record->type->formatValue($state),
                        ),
                ]),

            Section::make(DiscountResource::trans('sections.validity'))
                ->icon(Heroicon::OutlinedCalendarDays)
                ->iconColor(SemanticColor::Primary->value)
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('activated_at')
                        ->label(DiscountResource::trans('infolists.activated_at'))
                        ->since()
                        ->dateTimeTooltip(config('filament.date_time_format'))
                        ->placeholder('-'),

                    TextEntry::make('starts_at')
                        ->label(DiscountResource::trans('infolists.starts_at'))
                        ->since()
                        ->dateTimeTooltip(config('filament.date_time_format'))
                        ->placeholder('-'),

                    TextEntry::make('ends_at')
                        ->label(DiscountResource::trans('infolists.ends_at'))
                        ->since()
                        ->dateTimeTooltip(config('filament.date_time_format'))
                        ->placeholder('-'),
                ]),

            Grid::make(2)
                ->columnSpanFull()
                ->schema([
                    self::appliedToSection(
                        'products',
                        'sections.discounted_products',
                        Heroicon::OutlinedShoppingBag,
                    ),

                    self::appliedToSection(
                        'productTypes',
                        'sections.discounted_product_types',
                        Heroicon::OutlinedTag,
                    ),

                    self::appliedToSection(
                        'categories',
                        'sections.discounted_categories',
                        Heroicon::OutlinedSquares2x2,
                    ),

                    self::appliedToSection(
                        'brands',
                        'sections.discounted_brands',
                        Heroicon::OutlinedBuildingStorefront,
                    ),
                ]),
        ]);
    }

    /**
     * One of the four things a discount may be pinned to, listed the same way
     * whichever relation it is.
     */
    private static function appliedToSection(
        string $relation,
        string $sectionKey,
        Heroicon $icon,
    ): Section {
        return Section::make(DiscountResource::trans($sectionKey))
            ->icon($icon)
            ->iconColor(SemanticColor::Primary->value)
            ->schema([
                RepeatableEntry::make($relation)
                    ->hiddenLabel()
                    ->schema([
                        TextEntry::make('name')->hiddenLabel(),
                    ]),
            ]);
    }
}
