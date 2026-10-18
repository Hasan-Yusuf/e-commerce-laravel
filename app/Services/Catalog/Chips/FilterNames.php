<?php

declare(strict_types=1);

namespace App\Services\Catalog\Chips;

use App\Models\Brand;
use App\Models\Category;

/**
 * Names the brand or category records a listing state selects, so a chip can
 * read back the names for ids the URL carries.
 */
final class FilterNames
{
    /**
     * The localized names of the selected ids, or null when nothing valid is
     * selected.
     *
     * @param  class-string<Brand>|class-string<Category>  $model
     */
    public function for(string $model, mixed $ids): ?string
    {
        $ids = collect(is_array($ids) ? $ids : [])
            ->filter(static fn (mixed $id): bool => is_numeric($id))
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return null;
        }

        $names = $model::query()
            ->whereIn('id', $ids->all())
            ->get()
            ->map(static fn (Brand|Category $record): string => $record->name)
            ->implode(', ');

        return $names !== '' ? $names : null;
    }
}
