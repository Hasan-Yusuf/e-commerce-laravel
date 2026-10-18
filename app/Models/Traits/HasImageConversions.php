<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Enums\MimeType;

trait HasImageConversions
{
    /**
     * The uploaded image's WebP conversion and its responsive variants.
     *
     * Queued rather than generated during the request that stored the image, so
     * an upload answers at once and the conversion is left to the queue worker
     * the deployment runs (`php artisan queue:work`). Until it lands the
     * storefront serves the file the administrator uploaded.
     */
    public function addConversion(MimeType $type): void
    {
        $this
            ->addMediaConversion($type->extension())
            ->format($type->extension())
            ->queued()
            ->withResponsiveImages();
    }
}
