<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\CompareableEnum;
use App\Models\TypeSpec;
use App\Services\Catalog\SpecOptionsService;
use App\Support\HasTranslationKeys;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Contracts\HasLabel;

enum SpecValueType: string implements HasLabel
{
    use CompareableEnum, HasTranslationKeys;

    case TEXT = 'text';
    case NUMBER = 'number';
    case OPTIONS = 'options';
    case BOOLEAN = 'boolean';

    #[\Override]
    public function getLabel(): string
    {
        return self::trans($this->value);
    }

    /**
     * The product input that captures a value of this type, bound to the spec's
     * `specs_<locale>.<name>` state. The caller adds the label and whether the
     * value is required, and passes a prefix when the input is nested (the
     * type's "update the products" page captures one product per group).
     *
     * A number spec that presets its values (storage sizes) is picked from rather
     * than typed, so what it stores is one of the values it declared.
     */
    public function makeField(
        TypeSpec $spec,
        string $locale,
        string $statePathPrefix = '',
    ): Field {
        $statePath = "{$statePathPrefix}specs_{$locale}.{$spec->nameFor($locale)}";

        $input = match ($this) {
            self::NUMBER => $spec->presetsValues()
                ? Select::make($statePath)
                    ->options($spec->optionChoices($locale))
                    ->suffix($spec->unit)
                    ->dehydrateStateUsing(self::castNumber(...))
                : TextInput::make($statePath)
                    ->numeric()
                    ->suffix($spec->unit)
                    ->dehydrateStateUsing(self::castNumber(...)),
            self::OPTIONS => Select::make($statePath)
                ->options($spec->optionChoices($locale))
                ->live()
                ->afterStateUpdated(
                    $this->answerOtherLocale($spec, $locale, $statePathPrefix),
                ),
            self::BOOLEAN => Toggle::make($statePath)->default(false),
            self::TEXT => TextInput::make($statePath),
        };

        if (! $this->isLocalized()) {
            $input->columnSpanFull();
        }

        return $input;
    }

    /**
     * The handler that answers the same option in the other locale: an option
     * list is the one value type whose two locales name the same choice, so
     * picking a colour in English picks its Arabic twin rather than asking the
     * admin to find the same option again.
     */
    private function answerOtherLocale(
        TypeSpec $spec,
        string $locale,
        string $statePathPrefix,
    ): Closure {
        return function (Set $set, mixed $state) use ($spec, $locale, $statePathPrefix): void {
            $otherLocale = $this->otherLocale($locale);

            if ($otherLocale === null) {
                return;
            }

            $set(
                "{$statePathPrefix}specs_{$otherLocale}.".$spec->nameFor($otherLocale),
                app(SpecOptionsService::class)->mirrorLabel(
                    $spec,
                    $state,
                    $locale,
                    $otherLocale,
                ),
            );
        };
    }

    /**
     * The other locale a value is captured in, or null when the application
     * declares only one.
     */
    private function otherLocale(string $locale): ?string
    {
        foreach (array_keys(config('locales')) as $code) {
            $code = (string) $code;

            if ($code !== $locale) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Whether a value of this type reads differently in each locale, and so is
     * captured with an input per locale. A number or a yes/no is the same
     * everywhere, so it is captured once.
     */
    public function isLocalized(): bool
    {
        return $this->is([self::TEXT, self::OPTIONS]);
    }

    /**
     * Whether a product must supply a value of this type.
     */
    public function isRequired(): bool
    {
        return $this->isNot(self::BOOLEAN);
    }

    /**
     * The filter kinds this value type can be narrowed with: `range` only makes
     * sense against a number, so every other type is limited to `list`.
     *
     * @return array<int, SpecFilterType>
     */
    public function filterTypes(): array
    {
        return
            $this->is(self::NUMBER)
                ? [SpecFilterType::LIST, SpecFilterType::RANGE]
                : [SpecFilterType::LIST];
    }

    /**
     * Read a stored product value the way the form captured it: a boolean as
     * the locale's yes/no and a number with the spec's unit beside it.
     */
    public function getFormattedValue(mixed $value, ?string $unit = null): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($this) {
            self::BOOLEAN => $value
                ? __('catalog.product.spec_yes')
                : __('catalog.product.spec_no'),
            self::NUMBER => trim($value.($unit !== null ? " {$unit}" : '')),
            default => (string) $value,
        };
    }

    /**
     * Store a numeric input as a number, so a range filter can compare it.
     */
    private static function castNumber(mixed $state): int|float|null
    {
        return is_numeric($state) ? +$state : null;
    }
}
