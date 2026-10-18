<?php

declare(strict_types=1);

namespace App\Filament\Resources\Types\Schemas;

use App\Enums\SpecFilterType;
use App\Enums\SpecValueType;
use App\Filament\Resources\Types\TypeResource;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

/**
 * The typed specification builder, shared with the product wizard's inline
 * "create type" action.
 */
class SpecDefinitionsRepeater
{
    public static function make(): Repeater
    {
        return Repeater::make('specDefinitions')
            ->label(TypeResource::trans('fields.specifications'))
            ->relationship()
            ->orderColumn('sort_order')
            ->reorderable()
            ->collapsible()
            ->itemLabel(static fn (array $state): ?string => $state['name_en'] ?? null)
            ->addActionLabel(TypeResource::trans('actions.add_spec'))
            ->schema([
                TextInput::make('name_en')
                    ->label(TypeResource::trans('fields.spec_name_en'))
                    ->placeholder(
                        TypeResource::trans('placeholders.spec_name_en'),
                    )
                    ->required()
                    ->rule('min:2')
                    ->maxLength(255),

                TextInput::make('name_ar')
                    ->label(TypeResource::trans('fields.spec_name_ar'))
                    ->placeholder(
                        TypeResource::trans('placeholders.spec_name_ar'),
                    )
                    ->required()
                    ->rule('min:2')
                    ->maxLength(255),

                Select::make('value_type')
                    ->label(TypeResource::trans('fields.spec_value_type'))
                    ->options(SpecValueType::class)
                    ->default(SpecValueType::TEXT)
                    ->selectablePlaceholder(false)
                    ->required()
                    ->live()
                    ->afterStateUpdated(static function (
                        Get $get,
                        Set $set,
                        SpecValueType $state,
                    ): void {
                        if ($state->isNot(SpecValueType::NUMBER)) {
                            $set('unit', null);
                            $set(
                                'filter_type',
                                $get('is_filterable')
                                    ? SpecFilterType::LIST
                                    : null,
                            );
                        }

                        if ($state->isNot(SpecValueType::OPTIONS)) {
                            $set('options', []);
                        }
                    }),

                TextInput::make('unit')
                    ->label(TypeResource::trans('fields.spec_unit'))
                    ->placeholder(
                        TypeResource::trans('placeholders.spec_unit'),
                    )
                    ->visible(SpecValueTypeState::isNumber(...))
                    ->maxLength(255),

                static::optionsRepeater(),

                Toggle::make('is_filterable')
                    ->label(TypeResource::trans('fields.spec_is_filterable'))
                    ->default(false)
                    ->live()
                    ->afterStateUpdated(static function (
                        Set $set,
                        $state,
                    ): void {
                        $set(
                            'filter_type',
                            $state ? SpecFilterType::LIST : null,
                        );
                    }),

                Select::make('filter_type')
                    ->label(TypeResource::trans('fields.spec_filter_type'))
                    ->options(
                        static fn (Get $get): array => collect(
                            SpecValueTypeState::filterTypesFor($get),
                        )
                            ->mapWithKeys(static fn (
                                SpecFilterType $filterType,
                            ): array => [
                                $filterType->value => $filterType->getLabel(),
                            ])
                            ->all(),
                    )
                    ->default(SpecFilterType::LIST)
                    ->visible(
                        static fn (Get $get): bool => (bool) $get('is_filterable'),
                    )
                    ->required(
                        static fn (Get $get): bool => (bool) $get('is_filterable'),
                    )
                    ->rule(
                        static fn (Get $get): Closure => static function (
                            string $attribute,
                            mixed $value,
                            Closure $fail,
                        ) use ($get): void {
                            $isRange = $value === SpecFilterType::RANGE->value;

                            if (
                                $isRange
                                && SpecValueTypeState::valueTypeOf($get)?->isNot(
                                    SpecValueType::NUMBER,
                                )
                            ) {
                                $fail(
                                    TypeResource::trans(
                                        'validation.range_requires_number',
                                    ),
                                );
                            }
                        },
                    ),
            ])
            ->columns(2);
    }

    private static function optionsRepeater(): Repeater
    {
        return Repeater::make('options')
            ->label(
                static fn (Get $get): string => SpecValueTypeState::isNumber($get)
                    ? TypeResource::trans('fields.spec_values')
                    : TypeResource::trans('fields.spec_options'),
            )
            ->addActionLabel(
                static fn (Get $get): string => SpecValueTypeState::isNumber($get)
                    ? TypeResource::trans('actions.add_value')
                    : TypeResource::trans('actions.add_option'),
            )
            ->helperText(
                static fn (Get $get): ?string => SpecValueTypeState::isNumber($get)
                    ? TypeResource::trans('helpers.spec_values')
                    : null,
            )
            ->visible(
                static fn (Get $get): bool => SpecValueTypeState::isOptions($get) || SpecValueTypeState::isNumber($get),
            )
            ->columnSpanFull()
            ->schema(
                static fn (Get $get): array => (
                    SpecValueTypeState::isNumber($get)
                        ? static::getPresetValueFields()
                        : [
                            TextInput::make('label_en')
                                ->label(
                                    TypeResource::trans(
                                        'fields.spec_option_en',
                                    ),
                                )
                                ->required()
                                ->maxLength(255),

                            TextInput::make('label_ar')
                                ->label(
                                    TypeResource::trans(
                                        'fields.spec_option_ar',
                                    ),
                                )
                                ->required()
                                ->maxLength(255),
                        ]
                ),
            )
            ->columns(2)
            // Options are the whole point of an options spec, so it needs
            // at least one; a number spec's values are optional.
            ->minItems(
                static fn (Get $get): int => SpecValueTypeState::isOptions($get) ? 1 : 0,
            );
    }

    /**
     * The fields a numeric spec's values are declared with: the value itself,
     * entered once because a number reads the same in both locales, and the half
     * of the option pair it is mirrored into. Storing a pair keeps one shape
     * behind both kinds of spec, so a number spec's declared values are read
     * back with the same `optionLabels()` a set of labels is.
     *
     * @return array<int, Field>
     */
    private static function getPresetValueFields(): array
    {
        return [
            TextInput::make('label_en')
                ->label(TypeResource::trans('fields.spec_value'))
                ->numeric()
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(static function (Set $set, mixed $state): void {
                    $set('label_ar', is_scalar($state) ? (string) $state : null);
                }),

            Hidden::make('label_ar'),
        ];
    }
}
