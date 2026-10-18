<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\TypeSpec;
use Illuminate\Support\Collection;

/**
 * Resolves the specs an admin marked filterable from the ids a listing carries,
 * so the filter pipeline and the active-filter chips always agree on what a
 * listing can narrow by.
 */
final class FilterableSpecs
{
    /**
     * The requested specs the admin marked filterable, keyed by id: a spec left
     * off the filterable list (or an id that names no spec) is ignored rather
     * than narrowing the catalog.
     *
     * @param  array<int|string, mixed>  $ids
     * @return Collection<int, TypeSpec>
     */
    public function forIds(array $ids): Collection
    {
        $ids = collect($ids)
            ->filter(static fn (mixed $id): bool => is_numeric($id))
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return TypeSpec::query()
            ->whereIn('id', $ids->all())
            ->where('is_filterable', true)
            ->whereNotNull('filter_type')
            ->get()
            ->keyBy('id');
    }
}
