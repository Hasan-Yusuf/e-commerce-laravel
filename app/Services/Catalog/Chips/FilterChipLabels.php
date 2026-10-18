<?php

declare(strict_types=1);

namespace App\Services\Catalog\Chips;

use App\Models\TypeSpec;
use App\Support\Money;
use Illuminate\Support\Number;

/**
 * Formats the value side of a filter chip: a price bound, or a numeric spec's
 * range, each reading through the listing's own number formatting.
 */
final class FilterChipLabels
{
    /**
     * @param  array<string, mixed>  $state
     */
    public function price(array $state): ?string
    {
        return $this->range(
            $state['price_min'] ?? null,
            $state['price_max'] ?? null,
            Money::amount(...),
        );
    }

    /**
     * The range the shopper asked for with the spec's unit after it.
     */
    public function specRange(TypeSpec $spec, mixed $value): ?string
    {
        $range = is_array($value) ? $value : [];

        $unit = $spec->unit === null ? '' : ' '.$spec->unit;

        return $this->range(
            $range['min'] ?? null,
            $range['max'] ?? null,
            static fn (float $number): string => (string) Number::format($number, maxPrecision: 3).$unit,
        );
    }

    /**
     * A min/max a shopper asked for, or the locale's "from"/"to" wording when
     * only one end was given.
     */
    private function range(
        mixed $minimum,
        mixed $maximum,
        callable $format,
    ): ?string {
        $minimum = is_numeric($minimum) ? (float) $minimum : null;
        $maximum = is_numeric($maximum) ? (float) $maximum : null;

        if ($minimum === null && $maximum === null) {
            return null;
        }

        if ($minimum !== null && $maximum !== null) {
            return $format($minimum).' – '.$format($maximum);
        }

        if ($minimum !== null) {
            return __('catalog.filters.from', [
                'value' => $format($minimum),
            ]);
        }

        return __('catalog.filters.to', [
            'value' => $format($maximum),
        ]);
    }
}
