<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Collection;

/**
 * The banners the storefront shows. Reading them here rather than in the
 * controller keeps the homepage's hero and its rails coming from the same layer.
 */
class BannerService
{
    /**
     * How many slides the hero carries.
     */
    public const int HERO_LIMIT = 5;

    /**
     * The newest banners for the hero slider, with the image each slide shows.
     *
     * @return Collection<int, Banner>
     */
    public function getHomePageBanners(int $count = self::HERO_LIMIT): Collection
    {
        return Banner::query()
            ->with('media')
            ->latest()
            ->limit($count)
            ->get();
    }
}
