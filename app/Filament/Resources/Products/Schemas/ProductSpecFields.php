<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Type;
use App\Models\TypeSpec;
use Filament\Forms\Components\Field;

/**
 * Builds the product inputs for a type's spec definitions, and mirrors the
 * values that read the same in every locale.
 */
class ProductSpecFields
{
    /**
     * The typed value inputs for the selected type's spec definitions: one per
     * locale, except for the value types that read the same everywhere, which
     * are captured once and mirrored into every locale on save.
     *
     * @return array<int, Field>
     */
    public static function getTypeSpecFields(
        mixed $typeId,
        string $statePathPrefix = '',
    ): array {
        if (blank($typeId)) {
            return [];
        }

        $type = Type::query()
            ->with('specDefinitions')
            ->whereKey($typeId)
            ->first();

        if ($type === null) {
            return [];
        }

        return self::getSpecFields($type, $statePathPrefix);
    }

    /**
     * The same inputs for a type that is already loaded, so a page holding one
     * group of them per product builds them from one schema. The prefix is the
     * state path they hang under (ending in a dot), which the wizard leaves
     * empty and a nested group fills in.
     *
     * @return array<int, Field>
     */
    public static function getSpecFields(
        Type $type,
        string $statePathPrefix = '',
    ): array {
        $fields = [];

        foreach ($type->specDefinitions as $spec) {
            /** @var array<int, string> $locales */
            $locales = $spec->value_type->isLocalized()
                ? array_keys(config('locales'))
                : ['en'];

            foreach ($locales as $locale) {
                $fields[] = self::getTypeSpecValueField(
                    $spec,
                    $locale,
                    $statePathPrefix,
                );
            }
        }

        return $fields;
    }

    /**
     * The input that captures a product value for this spec: its value type
     * builds the field, so the spec only adds the label and whether the value is
     * required. A value that reads the same in every locale is captured once,
     * under the English key, and labelled in the current locale.
     */
    public static function getTypeSpecValueField(
        TypeSpec $spec,
        string $locale,
        string $statePathPrefix = '',
    ): Field {
        $valueType = $spec->value_type;
        $isLocalized = $valueType->isLocalized();

        return $valueType
            ->makeField($spec, $locale, $statePathPrefix)
            ->label($isLocalized ? $spec->nameFor($locale) : $spec->name)
            ->required($valueType->isRequired())
            ->columnSpan($isLocalized ? 1 : 'full');
    }

    /**
     * Store the single-input values under every locale's spec name, rebuilding
     * the Arabic values in schema order so the two locales stay in step.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mirrorNonLocalizedSpecValues(array $data): array
    {
        $typeId = $data['type_id'] ?? null;

        /** @var Type|null $type */
        $type = blank($typeId)
            ? null
            : Type::query()->with('specDefinitions')->find($typeId);

        if ($type === null) {
            return $data;
        }

        $specsAr = [];

        foreach ($type->specDefinitions as $spec) {
            $name = $spec->name_ar;

            $specsAr[$name] = $spec->value_type->isLocalized()
                ? $data['specs_ar'][$name] ?? null
                : $data['specs_en'][$spec->name_en] ?? null;
        }

        $data['specs_ar'] = $specsAr;

        return $data;
    }
}
