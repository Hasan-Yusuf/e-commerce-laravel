<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Type;

/**
 * Maps the spec names a type held before an edit onto the ones its schema
 * declares now, per locale, and re-keys a stored value list with them.
 */
final class SpecRenameMap
{
    /**
     * The names to carry over: the name a spec held before the edit mapped onto
     * the one the schema declares now, as `[locale, from, to]` triples.
     *
     * @param  array<int, array<string, mixed>>  $previous
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    public function for(Type $type, array $previous): array
    {
        $renames = [];

        foreach ($type->specDefinitions as $spec) {
            $before = $previous[$spec->id] ?? null;

            if ($before === null) {
                continue;
            }

            foreach (['en' => 'name_en', 'ar' => 'name_ar'] as $locale => $attribute) {
                $from = (string) $before[$attribute];
                $to = $spec->{$attribute};

                if ($from !== $to) {
                    $renames[] = [$locale, $from, $to];
                }
            }
        }

        return $renames;
    }

    /**
     * Replace a key without disturbing the order the values around it are held
     * in, so a renamed spec reads in the place the schema declares it.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function renameKey(array $values, string $from, string $to): array
    {
        $renamed = [];

        foreach ($values as $name => $value) {
            $renamed[$name === $from ? $to : $name] = $value;
        }

        return $renamed;
    }
}
