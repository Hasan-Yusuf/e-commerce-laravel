<?php

declare(strict_types=1);

namespace App\Filament\Resources\Types\Schemas;

use App\Enums\SpecFilterType;
use App\Enums\SpecValueType;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Reads the spec's chosen `value_type` from the form state and answers what
 * follows from it, so the repeater's reactive fields share one reading.
 */
class SpecValueTypeState
{
    /**
     * The `value_type` state: an enum once the select has cast it, a string
     * while the form is still being filled.
     */
    public static function valueTypeOf(Get $get): ?SpecValueType
    {
        $state = $get('value_type');

        if ($state instanceof SpecValueType) {
            return $state;
        }

        if (\is_string($state)) {
            return SpecValueType::tryFrom($state);
        }

        return null;
    }

    public static function isNumber(Get $get): bool
    {
        return self::valueTypeOf($get)?->is(SpecValueType::NUMBER) ?? false;
    }

    public static function isOptions(Get $get): bool
    {
        return self::valueTypeOf($get)?->is(SpecValueType::OPTIONS) ?? false;
    }

    /**
     * The filter kinds the chosen value type allows.
     *
     * @return array<int, SpecFilterType>
     */
    public static function filterTypesFor(Get $get): array
    {
        return self::valueTypeOf($get)?->filterTypes() ?? [SpecFilterType::LIST];
    }
}
