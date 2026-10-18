<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Type;
use App\Models\TypeSpec;
use Illuminate\Support\Arr;

/**
 * Compares a type's spec schema before and after an edit, so the products filled
 * in against the old shape can be flagged as out of step.
 */
final class SpecSchemaComparator
{
    /**
     * The specs a type declares right now, by id — the shape the comparison
     * against the schema is taken before an edit and after it.
     *
     * @return array<int, array<string, mixed>>
     */
    public function snapshot(Type $type): array
    {
        return $type
            ->specDefinitions
            ->mapWithKeys(static fn (TypeSpec $spec): array => [
                $spec->id => [
                    'name_en' => $spec->name_en,
                    'name_ar' => $spec->name_ar,
                    'value_type' => $spec->value_type->value,
                    'options' => $spec->options ?? [],
                ],
            ])
            ->all();
    }

    /**
     * Whether an edit to the schema left the products' stored values wrong or
     * missing: a spec that was added, removed or renamed, one whose value type
     * changed, and one whose options changed, all ask for a value the products
     * either do not hold or no longer answer. A cosmetic change — the unit, the
     * order, whether the spec is filterable — leaves what is stored alone.
     *
     * @param  array<int, array<string, mixed>>  $previous
     */
    public function affectsProducts(Type $type, array $previous): bool
    {
        $current = $this->snapshot($type);

        if (
            array_diff_key($current, $previous) !== []
            || array_diff_key($previous, $current) !== []
        ) {
            return true;
        }

        foreach ($current as $id => $spec) {
            $before = $previous[$id];

            if (
                $spec['name_en'] !== $before['name_en']
                || $spec['name_ar'] !== $before['name_ar']
                || $spec['value_type'] !== $before['value_type']
                || ! $this->sameOptions($spec['options'], $before['options'])
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether two option lists hold the same entries. They are read as a set —
     * reordering them reorders the picker, it does not invalidate a value — so
     * each entry is reduced to a key-sorted form before an identity comparison.
     *
     * @param  array<int, mixed>  $options
     * @param  array<int, mixed>  $previous
     */
    private function sameOptions(array $options, array $previous): bool
    {
        return $this->canonical($options) === $this->canonical($previous);
    }

    /**
     * @param  array<int, mixed>  $options
     * @return array<int, string>
     */
    private function canonical(array $options): array
    {
        $entries = array_map(
            static fn (mixed $option): string => (string) json_encode(
                is_array($option) ? Arr::sortRecursive($option) : $option,
            ),
            $options,
        );

        sort($entries);

        return $entries;
    }
}
