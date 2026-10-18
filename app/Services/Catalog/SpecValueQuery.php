<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\SpecValueType;
use App\Models\TypeSpec;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;

/**
 * Reads a spec's stored product value out of the `specs_en` / `specs_ar` JSON
 * column for the storefront spec filters.
 *
 * The value is addressed with Laravel's own JSON selector (`specs_en->Operating
 * System`), so the engine writes the extraction: SQLite reads it with
 * `json_extract()` and hands the value back with its JSON type (a number is a
 * number), while MySQL's grammar reads the same value through
 * `json_unquote(json_extract())`, which unquotes a string.
 *
 * What that selector comparison cannot do on its own is compare *types*: a
 * filter value always arrives as text, because it comes from a query string,
 * and `json_extract()` hands back the 16 SQLite stored as an integer, so
 * `where('specs_en->Storage', '512')` matches nothing (measured, not assumed).
 * `text()` therefore casts, and `numeric()` casts to a number for a range, for
 * the same reason.
 *
 * A yes/no is the one value Laravel handles for us: handing `where()` a real
 * boolean makes the query a JSON boolean comparison (`json_extract(...) = true`
 * rather than the unquoted text), which each engine matches against the JSON
 * it stored. So the filter converts the answer it was given into a boolean
 * instead of assembling spellings of it here (`applyValues()`).
 *
 * `whereJsonContains()` is deliberately not used. It asks whether a JSON
 * document *contains* a value, which needs the document to be an array or an
 * object: MySQL's `json_contains()` is an error for a scalar, and SQLite's
 * `json_each()` hands a scalar back as one row that then has to be compared by
 * type anyway (the same "16" against 16 problem). Every spec value is a scalar,
 * so the comparison is an equality — with the casts above — rather than a
 * containment.
 *
 * The selector quotes the key, so a spec name holding spaces or dots addresses
 * one key rather than a nested path. A name holding "->" would read as a nested
 * path, which no spec name in this catalogue does.
 */
class SpecValueQuery
{
    /**
     * The spec's value as text, for comparing it with a stored label.
     */
    public function text(Builder $query, TypeSpec $spec, string $locale): Expression
    {
        /** @var literal-string $expression */
        $expression = "cast({$this->wrap($query, $spec, $locale)} as char)";

        return new Expression($expression);
    }

    /**
     * The spec's value as a number, for a min/max range.
     */
    public function numeric(Builder $query, TypeSpec $spec, string $locale): Expression
    {
        /** @var literal-string $expression */
        $expression = "cast({$this->wrap($query, $spec, $locale)} as decimal(20,6))";

        return new Expression($expression);
    }

    /**
     * Narrow the query to the products whose value for the spec is one of the
     * given ones.
     *
     * @param  array<int, string>  $values
     */
    public function applyValues(
        Builder $query,
        TypeSpec $spec,
        string $locale,
        array $values,
    ): void {
        if ($spec->value_type->is(SpecValueType::BOOLEAN)) {
            $this->applyBooleanValues($query, $spec, $locale, $values);

            return;
        }

        $query->whereIn(
            $this->text($query, $spec, $locale),
            array_values($values),
        );
    }

    /**
     * The distinct values of every list spec among the specs, resolved in one
     * query tagged per spec.
     *
     * A spec's values live behind its own JSON selector, so asking the database
     * once per spec would make a listing's query count grow with the number of
     * filters. Selecting every selector at once — aliased by spec id, deduped by
     * the database — costs one query however many specs there are; the values
     * are folded per spec here (declared values merged, canonical form, order).
     *
     * @param  Collection<int, TypeSpec>  $specs
     * @return array<int, array<string, string>> Spec id => stored value => label.
     */
    public function distinctValuesFor(
        Collection $specs,
        Builder $query,
        string $locale,
    ): array {
        $listSpecs = $specs
            ->reject(static fn (TypeSpec $spec): bool => $spec->usesRangeFilter())
            ->values();

        if ($listSpecs->isEmpty()) {
            return [];
        }

        $columns = $listSpecs
            ->map(
                fn (TypeSpec $spec): string => $this->wrap($query, $spec, $locale).' as '.self::alias($spec),
            )
            ->implode(', ');

        // The base query is read as rows rather than models: these are spec
        // values, not products.
        $rows = (clone $query->getQuery())
            ->reorder()
            ->selectRaw($columns)
            ->distinct()
            ->get();

        return $listSpecs
            ->mapWithKeys(function (TypeSpec $spec) use ($rows, $locale): array {
                $values = $rows
                    ->pluck(self::alias($spec))
                    ->filter(static fn (mixed $value): bool => $value !== null && $value !== '')
                    ->map(
                        fn (mixed $value): string => $this->canonical(
                            $spec,
                            (string) $value,
                        ),
                    );

                foreach ($spec->optionValues() as $declared) {
                    $values->push((string) $declared);
                }

                return [
                    (int) $spec->getKey() => $this
                        ->order($spec, $locale, $values->unique()->values())
                        ->mapWithKeys(static fn (string $value): array => [
                            $value => $spec->valueLabel($value),
                        ])
                        ->all(),
                ];
            })
            ->all();
    }

    /**
     * The column alias one spec's values are selected under.
     */
    private static function alias(TypeSpec $spec): string
    {
        return 'spec_value_'.$spec->getKey();
    }

    /**
     * A yes/no narrows through Laravel's own JSON boolean comparison: the
     * answers the filter was given are read as booleans, and asking for one of
     * them hands `where()` a real boolean, which is what the framework writes
     * through as the JSON literal. Asking for both answers narrows nothing, so
     * that is left alone rather than written as a comparison that always holds.
     *
     * @param  array<int, string>  $values
     */
    private function applyBooleanValues(
        Builder $query,
        TypeSpec $spec,
        string $locale,
        array $values,
    ): void {
        $answers = collect($values)->map(
            static fn (mixed $value): bool => filter_var(
                $value,
                FILTER_VALIDATE_BOOL,
            ),
        )
            ->unique();

        if ($answers->count() > 1) {
            return;
        }

        $query->where($this->selector($spec, $locale), $answers->first());
    }

    /**
     * The JSON selector naming one spec's value inside the specs object.
     */
    private function selector(TypeSpec $spec, string $locale): string
    {
        $column = TypeSpec::localeColumn($locale);

        return "specs_{$column}->".$spec->nameFor($locale);
    }

    /**
     * The selector as the query's own grammar spells it, so the extraction is
     * written by the engine that will run it.
     */
    private function wrap(Builder $query, TypeSpec $spec, string $locale): string
    {
        return $query
            ->getQuery()
            ->getGrammar()
            ->wrap($this->selector($spec, $locale));
    }

    /**
     * A stored value in the one form every reader agrees on: a yes/no is folded
     * to "1"/"0", the spelling SQLite already reads and MySQL spells out. The
     * choice in the sidebar, its label and the chip that removes it all speak
     * this form, whichever engine read it.
     */
    private function canonical(TypeSpec $spec, string $value): string
    {
        if ($spec->value_type->isNot(SpecValueType::BOOLEAN)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL) ? '1' : '0';
    }

    /**
     * Order the values the way the spec presents them: a number or a yes/no
     * numerically, an options list in its declared order, anything else
     * alphabetically.
     *
     * @param  Collection<int, string>  $values
     * @return Collection<int, string>
     */
    private function order(
        TypeSpec $spec,
        string $locale,
        Collection $values,
    ): Collection {
        if ($spec->value_type->is([SpecValueType::NUMBER, SpecValueType::BOOLEAN])) {
            return $values
                ->sortBy(
                    static fn (string $value): float => is_numeric($value) ? (float) $value : 0.0,
                )
                ->values();
        }

        if ($spec->value_type->is(SpecValueType::OPTIONS)) {
            $order = array_flip($spec->optionLabels($locale));

            return $values->sortBy(static fn (string $value): int => $order[$value] ?? PHP_INT_MAX)->values();
        }

        return $values->sort()->values();
    }
}
