<?php

declare(strict_types=1);

namespace App\Services\Catalog\Chips;

use Illuminate\Support\Arr;

/**
 * Builds the URL a chip points at: the listing's own URL with just that filter
 * removed (and pagination reset). The chips are not always rendered by a
 * request — a Livewire re-render holds the state, and the URL it belongs to is
 * the listing's, not the endpoint that answered.
 */
final class FilterChipUrl
{
    /**
     * The listing's URL with the given query parameters removed. Pagination is
     * not part of the listing's state, so removing a filter lands on its first
     * page by itself.
     *
     * @param  array<string, mixed>  $state
     * @param  array<int, string>  $keys
     */
    public function without(array $state, string $path, array $keys): string
    {
        /** @var array<string, mixed> $state */
        $state = Arr::except($state, $keys);

        return $this->forState($state, $path);
    }

    /**
     * The listing's URL with one spec's selection dropped, keeping the others.
     *
     * @param  array<string, mixed>  $state
     */
    public function withoutSpec(array $state, string $path, int $id): string
    {
        $remaining = Arr::except(
            is_array($state['specs'] ?? null) ? $state['specs'] : [],
            [$id],
        );

        /** @var array<string, mixed> $state */
        $state = Arr::except($state, ['specs']);

        // Dropping the last spec leaves nothing to narrow by: the listing's own
        // URL rather than one carrying an empty parameter.
        if ($remaining !== []) {
            $state['specs'] = $remaining;
        }

        return $this->forState($state, $path);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function forState(array $state, string $path): string
    {
        return $state === []
            ? $path
            : $path.'?'.Arr::query($state);
    }
}
