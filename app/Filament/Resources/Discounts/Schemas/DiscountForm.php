<?php

declare(strict_types=1);

namespace App\Filament\Resources\Discounts\Schemas;

use App\Enums\DiscountType;
use App\Filament\Forms\Components\DiscountValueInput;
use App\Filament\Resources\Discounts\DiscountResource;
use App\Models\Brand;
use App\Models\Category;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class DiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(
                DiscountResource::trans('sections.general_information'),
            )->schema([
                TextInput::make('name')
                    ->label(DiscountResource::trans('fields.name'))
                    ->required(),

                Radio::make('type')
                    ->label(DiscountResource::trans('fields.type'))
                    ->options(DiscountType::class)
                    ->default(DiscountType::FIXED_AMOUNT)
                    ->inline()
                    ->live()
                    ->required(),

                DiscountValueInput::make('value')
                    ->label(DiscountResource::trans('fields.value'))
                    ->numeric()
                    ->required()
                    ->gt('0')
                    ->helperText(DiscountResource::trans('helpers.value')),
            ]),

            Section::make(DiscountResource::trans('sections.validity'))->schema(
                [
                    DateTimePicker::make('starts_at')
                        ->label(DiscountResource::trans('fields.starts_at'))
                        ->default(now())
                        ->displayFormat('d/m/Y h:i A'),

                    DateTimePicker::make('ends_at')
                        ->label(DiscountResource::trans('fields.ends_at'))
                        ->default(now()->addDays(3))
                        ->displayFormat('d/m/Y h:i A')
                        ->after('starts_at'),

                    Group::make([
                        Toggle::make('active')
                            ->label(DiscountResource::trans('fields.active'))
                            ->default(true)
                            ->live()
                            ->afterStateUpdated(
                                static fn (Set $set, $state) => $set(
                                    'activated_at',
                                    $state ? now() : null,
                                ),
                            ),

                        DateTimePicker::make('activated_at')
                            ->label(
                                DiscountResource::trans('fields.activated_at'),
                            )
                            ->default(now())
                            ->displayFormat('d/m/Y h:i A')
                            ->live()
                            ->disabled(static fn (Get $get) => $get('active')),
                    ]),
                ],
            ),

            Section::make(DiscountResource::trans('sections.apply_to'))
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    Select::make('products')
                        ->label(DiscountResource::trans('fields.products'))
                        ->relationship('products', 'name')
                        ->multiple()
                        ->searchable()
                        ->searchDebounce(300),

                    Select::make('categories')
                        ->label(DiscountResource::trans('fields.categories'))
                        ->relationship('categories')
                        ->getOptionLabelFromRecordUsing(
                            static fn (Category $record) => $record->name,
                        )
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->searchDebounce(300),

                    Select::make('productTypes')
                        ->label(DiscountResource::trans('fields.product_types'))
                        ->relationship('productTypes', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->searchDebounce(300),

                    Select::make('brands')
                        ->label(DiscountResource::trans('fields.brands'))
                        ->relationship('brands')
                        ->getOptionLabelFromRecordUsing(
                            static fn (Brand $record) => $record->name,
                        )
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->searchDebounce(300),
                ]),
        ]);
    }
}
