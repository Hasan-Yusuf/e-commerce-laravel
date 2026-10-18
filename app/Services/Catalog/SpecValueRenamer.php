<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\Type;

/**
 * Carries a type's stored product values across a spec rename, so a rename
 * cannot leave a catalogue without its values. A rename is the only schema edit
 * whose meaning can be recovered from what is stored — the rest rebuild from
 * the schema on the panel's "update the products" page.
 */
final class SpecValueRenamer
{
    public function __construct(
        private SpecRenameMap $map,
    ) {}

    /**
     * Move the values of the specs that were renamed onto the names the schema
     * declares now, so a rename cannot leave a catalogue without its values.
     * Answers how many products were moved.
     *
     * @param  array<int, array<string, mixed>>  $previous
     */
    public function rename(Type $type, array $previous): int
    {
        $renames = $this->map->for($type, $previous);

        if ($renames === []) {
            return 0;
        }

        $moved = 0;

        foreach ($type->products()->get() as $product) {
            /** @var Product $product */
            /** @var array<string, array<string, mixed>> $specs */
            $specs = [
                'en' => $product->specs_en ?? [],
                'ar' => $product->specs_ar ?? [],
            ];

            $before = $specs;

            foreach ($renames as [$locale, $from, $to]) {
                if (! array_key_exists($from, $specs[$locale])) {
                    continue;
                }

                $specs[$locale] = $this->map->renameKey(
                    $specs[$locale],
                    $from,
                    $to,
                );
            }

            if ($specs === $before) {
                continue;
            }

            $product->update([
                'specs_en' => $specs['en'],
                'specs_ar' => $specs['ar'],
            ]);

            $moved++;
        }

        return $moved;
    }
}
