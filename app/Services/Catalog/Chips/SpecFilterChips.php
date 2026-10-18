<?php

declare(strict_types=1);

namespace App\Services\Catalog\Chips;

use App\Models\TypeSpec;
use App\Services\Catalog\FilterableSpecs;
use Illuminate\Support\Collection;

/**
 * Builds one chip per applied spec, each removing only its own spec so the other
 * specs survive.
 */
final class SpecFilterChips
{
    public function __construct(
        private FilterableSpecs $specs,
        private FilterChipLabels $labels,
        private FilterChipUrl $url,
    ) {}

    /**
     * @param  array<string, mixed>  $state
     * @return array<int, array{label: string, url: string, filter: string, value: int|string|null}>
     */
    public function build(array $state, string $path): array
    {
        $selected = $state['specs'] ?? null;

        if (! is_array($selected) || $selected === []) {
            return [];
        }

        $specs = $this->specs->forIds(array_keys($selected));

        if ($specs->isEmpty()) {
            return [];
        }

        $chips = [];

        foreach ($selected as $id => $value) {
            $chip = $this->chip($specs, $id, $value, $state, $path);

            if ($chip === null) {
                continue;
            }

            $chips[] = $chip;
        }

        return $chips;
    }

    /**
     * @param  Collection<int, TypeSpec>  $specs
     * @param  array<string, mixed>  $state
     * @return array{label: string, url: string, filter: string, value: int|string|null}|null
     */
    private function chip(
        Collection $specs,
        mixed $id,
        mixed $value,
        array $state,
        string $path,
    ): ?array {
        $spec = is_numeric($id) ? $specs->get((int) $id) : null;

        if ($spec === null) {
            return null;
        }

        $label = $spec->usesRangeFilter()
            ? $this->labels->specRange($spec, $value)
            : $this->listLabel($spec, $value);

        if ($label === null) {
            return null;
        }

        return [
            'label' => $spec->nameFor(app()->getLocale()).': '.$label,
            'url' => $this->url->withoutSpec($state, $path, (int) $id),
            'filter' => 'specs',
            'value' => (int) $id,
        ];
    }

    /**
     * The selected labels of a list spec, in the order the shopper picked them.
     */
    private function listLabel(TypeSpec $spec, mixed $value): ?string
    {
        $labels = collect(is_array($value) ? $value : [$value])
            ->filter(
                static fn (mixed $entry): bool => is_scalar($entry) && $entry !== '',
            )
            ->map(static fn (mixed $entry): string => $spec->valueLabel((string) $entry))
            ->unique()
            ->values();

        return $labels->isEmpty() ? null : $labels->implode(', ');
    }
}
