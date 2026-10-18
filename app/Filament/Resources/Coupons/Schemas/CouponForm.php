<?php

declare(strict_types=1);

namespace App\Filament\Resources\Coupons\Schemas;

use App\Enums\DiscountType;
use App\Enums\Unit;
use App\Filament\Forms\Components\DiscountValueInput;
use App\Filament\Forms\Components\UnitInput;
use App\Filament\Resources\Coupons\CouponResource;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(
                CouponResource::trans('sections.general_information'),
            )->schema([
                TextInput::make('code')
                    ->label(CouponResource::trans('fields.code'))
                    ->required()
                    ->maxLength(50),

                Textarea::make('description')
                    ->label(CouponResource::trans('fields.description'))
                    ->rows(2),

                Radio::make('discount_type')
                    ->label(CouponResource::trans('fields.type'))
                    ->options(DiscountType::class)
                    ->default(DiscountType::FIXED_AMOUNT)
                    ->inline()
                    ->live()
                    ->required(),

                DiscountValueInput::make('discount_value')
                    ->typeField('discount_type')
                    ->label(CouponResource::trans('fields.value'))
                    ->numeric()
                    ->required()
                    ->gt('0')
                    ->helperText(CouponResource::trans('helpers.value')),

                UnitInput::make('maximum_discount')
                    ->label(CouponResource::trans('fields.maximum_discount'))
                    ->numeric()
                    ->unit(Unit::CURRENCY)
                    ->helperText(CouponResource::trans('helpers.maximum_discount')),

                UnitInput::make('min_order_amount')
                    ->label(CouponResource::trans('fields.min_order_amount'))
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->unit(Unit::CURRENCY),

                TextInput::make('usage_limit')
                    ->label(CouponResource::trans('fields.usage_limit'))
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->helperText(CouponResource::trans('helpers.usage_limit')),
            ]),

            Section::make(CouponResource::trans('sections.validity'))->schema(
                [
                    DateTimePicker::make('activated_at')
                        ->label(CouponResource::trans('fields.activated_at'))
                        ->default(now())
                        ->displayFormat('d/m/Y h:i A'),

                    DateTimePicker::make('starts_at')
                        ->label(CouponResource::trans('fields.starts_at'))
                        ->default(now())
                        ->displayFormat('d/m/Y h:i A'),

                    DateTimePicker::make('expires_at')
                        ->label(CouponResource::trans('fields.expires_at'))
                        ->default(now()->addDays(3))
                        ->displayFormat('d/m/Y h:i A')
                        ->after('starts_at'),
                ],
            ),
        ]);
    }
}
