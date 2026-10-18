<?php

declare(strict_types=1);

namespace App\Services\Catalog\Filters;

/**
 * Normalises the raw value a listing carries for one spec, so the filter and the
 * chips read the same form of it.
 */
final class SpecFilterValues
{
    /**
     * The selected list values as non-empty strings.
     *
     * @return array<int, string>
     */
    public function list(mixed $value): array
    {
        return collect(is_array($value) ? $value : [$value])
            ->filter(
                static fn (mixed $entry): bool => is_scalar($entry) && $entry !== '',
            )
            ->map(static fn (mixed $entry): string => (string) $entry)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The numeric bounds a range spec was filtered by.
     *
     * @return array{min: float|null, max: float|null}
     */
    public function range(mixed $value): array
    {
        $range = is_array($value) ? $value : [];

        return [
            'min' => $this->toNumber($range['min'] ?? null),
            'max' => $this->toNumber($range['max'] ?? null),
        ];
    }

    private function toNumber(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
