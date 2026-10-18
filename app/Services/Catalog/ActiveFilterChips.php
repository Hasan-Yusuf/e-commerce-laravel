<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Services\Catalog\Chips\FilterChipLabels;
use App\Services\Catalog\Chips\FilterChipUrl;
use App\Services\Catalog\Chips\FilterNames;
use App\Services\Catalog\Chips\SpecFilterChips;
use App\Services\Catalog\Filters\AvailabilityFilter;

/**
 * Builds the summary chips for the filters currently applied to a catalog
 * listing, each with a URL that removes just that filter (and resets pagination)
 * and the filter it stands for, so a listing that answers its own filters can
 * remove one without a page load either.
 *
 * The chips are built from the listing's own state and URL rather than from the
 * request, because they are not always rendered by a request: a Livewire
 * re-render holds the state, and the URL it belongs to is the listing's, not the
 * endpoint that answered.
 */
class ActiveFilterChips
{
    public function __construct(
        private FilterNames $names,
        private FilterChipLabels $labels,
        private FilterChipUrl $url,
        private SpecFilterChips $specs,
    ) {}

    /**
     * @param  array<string, mixed>  $state  Everything the listing's URL carries.
     * @param  string  $path  The listing's own URL.
     * @return array<int, array{label: string, url: string, filter: string, value: int|string|null}>
     */
    public function build(array $state, string $path): array
    {
        $chips = [];

        foreach ([
            $this->brandChip($state, $path),
            $this->categoryChip($state, $path),
            $this->priceChip($state, $path),
            $this->availabilityChip($state, $path),
            $this->discountedChip($state, $path),
        ] as $chip) {
            if ($chip === null) {
                continue;
            }

            $chips[] = $chip;
        }

        return array_merge($chips, $this->specs->build($state, $path));
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{label: string, url: string, filter: string, value: int|string|null}|null
     */
    private function brandChip(array $state, string $path): ?array
    {
        $names = $this->names->for(Brand::class, $state['brand'] ?? []);

        if ($names === null) {
            return null;
        }

        /** @var string $label */
        $label = __('catalog.filters.brand');

        return [
            'label' => $label.': '.$names,
            'url' => $this->url->without($state, $path, ['brand']),
            'filter' => 'brand',
            'value' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{label: string, url: string, filter: string, value: int|string|null}|null
     */
    private function categoryChip(array $state, string $path): ?array
    {
        $names = $this->names->for(Category::class, $state['category'] ?? []);

        if ($names === null) {
            return null;
        }

        /** @var string $label */
        $label = __('catalog.filters.category');

        return [
            'label' => $label.': '.$names,
            'url' => $this->url->without($state, $path, ['category']),
            'filter' => 'category',
            'value' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{label: string, url: string, filter: string, value: int|string|null}|null
     */
    private function priceChip(array $state, string $path): ?array
    {
        $label = $this->labels->price($state);

        if ($label === null) {
            return null;
        }

        /** @var string $priceLabel */
        $priceLabel = __('catalog.filters.price');

        return [
            'label' => $priceLabel.': '.$label,
            'url' => $this->url->without($state, $path, ['price_min', 'price_max']),
            'filter' => 'price',
            'value' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{label: string, url: string, filter: string, value: int|string|null}|null
     */
    private function availabilityChip(array $state, string $path): ?array
    {
        if (($state['availability'] ?? null) !== AvailabilityFilter::IN_STOCK) {
            return null;
        }

        return [
            'label' => __('catalog.filters.in_stock'),
            'url' => $this->url->without($state, $path, ['availability']),
            'filter' => 'availability',
            'value' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{label: string, url: string, filter: string, value: int|string|null}|null
     */
    private function discountedChip(array $state, string $path): ?array
    {
        if (! filter_var($state['discounted'] ?? false, FILTER_VALIDATE_BOOL)) {
            return null;
        }

        return [
            'label' => __('catalog.filters.discounted'),
            'url' => $this->url->without($state, $path, ['discounted']),
            'filter' => 'discounted',
            'value' => null,
        ];
    }
}
