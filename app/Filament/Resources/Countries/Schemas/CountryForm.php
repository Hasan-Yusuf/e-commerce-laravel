<?php

declare(strict_types=1);

namespace App\Filament\Resources\Countries\Schemas;

use App\Filament\Resources\Countries\CountryResource;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make([
                Grid::make(3)->schema([
                    TextInput::make('name_en')
                        ->label(CountryResource::trans('fields.name_en'))
                        ->placeholder(
                            CountryResource::trans('placeholders.name_en'),
                        )
                        ->autocapitalize()
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(static function (
                            Set $set,
                            $operation,
                            $state,
                        ) {
                            if ($operation === 'Edit') {
                                return;
                            }

                            $set('slug', Str::slug($state));
                        }),

                    TextInput::make('name_ar')
                        ->label(CountryResource::trans('fields.name_ar'))
                        ->placeholder(
                            CountryResource::trans('placeholders.name_ar'),
                        )
                        ->autocapitalize()
                        ->required(),

                    TextInput::make('slug')
                        ->label(CountryResource::trans('fields.slug'))
                        ->helperText(CountryResource::trans('helpers.slug'))
                        ->required()
                        ->dehydrated()
                        ->disabled(),
                ]),

                Section::make(
                    CountryResource::trans('sections.number_information'),
                )
                    ->schema([
                        TextInput::make('calling_code')
                            ->label(
                                CountryResource::trans('fields.calling_code'),
                            )
                            ->placeholder(
                                CountryResource::trans(
                                    'placeholders.calling_code',
                                ),
                            )
                            ->required(),

                        TextInput::make('phone_length')
                            ->label(
                                CountryResource::trans('fields.phone_length'),
                            )
                            ->placeholder(
                                CountryResource::trans(
                                    'placeholders.phone_length',
                                ),
                            )
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(2),
            ])->columnSpanFull(),
        ]);
    }
}
