<?php

declare(strict_types=1);

namespace App\Services\Catalog\Filters;

use App\Models\TypeSpec;
use App\Services\Catalog\FilterableSpecs;
use App\Services\Catalog\ProductFilter;
use App\Services\Catalog\SpecValueQuery;
use Illuminate\Database\Eloquent\Builder;

/**
 * Narrows the catalog by the typed spec values a product carries, driven by
 * whatever the admin marked filterable.
 *
 * A spec with `filter_type = list` matches its stored value exactly; one with
 * `filter_type = range` matches a numeric min/max. Both read the value through
 * SpecValueQuery, so the filter and the values the storefront offers always
 * speak the same form of the value.
 */
class SpecFilter implements ProductFilter
{
    public function __construct(
        private readonly SpecValueQuery $values,
        private readonly FilterableSpecs $specs,
        private readonly SpecFilterValues $bounds,
    ) {}

    /**
     * @return array<int, string>
     */
    #[\Override]
    public function params(): array
    {
        return ['specs'];
    }

    #[\Override]
    public function apply(Builder $query, array $params): void
    {
        $selected = $params['specs'] ?? null;

        if (! is_array($selected) || $selected === []) {
            return;
        }

        $specs = $this->specs->forIds(array_keys($selected));

        if ($specs->isEmpty()) {
            return;
        }

        $locale = app()->getLocale();

        foreach ($selected as $id => $value) {
            $spec = is_numeric($id) ? $specs->get((int) $id) : null;

            if ($spec === null) {
                continue;
            }

            if ($spec->usesRangeFilter()) {
                $this->applyRange($query, $spec, $locale, $value);

                continue;
            }

            $this->applyList($query, $spec, $locale, $value);
        }
    }

    /**
     * Match products whose value for the spec is one of the selected ones.
     */
    private function applyList(
        Builder $query,
        TypeSpec $spec,
        string $locale,
        mixed $value,
    ): void {
        $values = $this->bounds->list($value);

        if ($values === []) {
            return;
        }

        $this->values->applyValues($query, $spec, $locale, $values);
    }

    /**
     * Match products whose numeric value for the spec falls inside the bounds.
     */
    private function applyRange(
        Builder $query,
        TypeSpec $spec,
        string $locale,
        mixed $value,
    ): void {
        $bounds = $this->bounds->range($value);
        $minimum = $bounds['min'];
        $maximum = $bounds['max'];

        if ($minimum === null && $maximum === null) {
            return;
        }

        $expression = $this->values->numeric($query, $spec, $locale);

        if ($minimum !== null) {
            $query->where($expression, '>=', $minimum);
        }

        if ($maximum !== null) {
            $query->where($expression, '<=', $maximum);
        }
    }
}
