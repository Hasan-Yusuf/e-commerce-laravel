<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

/**
 * Whether a seeder may lay down its one sample picture.
 *
 * Every image written through the media library is converted to WebP and given
 * responsive variants, which is the slowest part of a seed, so a seeder asks
 * here rather than picturing every row it writes (see config/seeders.php). A
 * test run never does: it only needs the rows.
 */
trait SeedsSampleImages
{
    protected function shouldSeedSampleImage(): bool
    {
        return (bool) config('seeders.images') && ! app()->runningUnitTests();
    }
}
