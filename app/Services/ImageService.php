<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ResponsiveImageData;
use App\Enums\MimeType;
use App\Models\Product;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Resolves the file the storefront serves for a piece of media: the WebP
 * conversion the uploads are converted into, or the file as it was stored when
 * that conversion is missing. The choice lives here so no view has to make it.
 *
 * It holds no state, so it is reached statically rather than resolved out of the
 * container. Being stateless, an instance reaches the same methods just as well.
 */
class ImageService
{
    public static function forMedia(?Media $media): ?ResponsiveImageData
    {
        if ($media === null) {
            return null;
        }

        $conversion = MimeType::IMAGE_WEBP->conversion();

        $srcset = $media->hasGeneratedConversion($conversion)
            ? $media->getSrcset($conversion)
            : $media->getSrcset();

        return new ResponsiveImageData(
            src: $media->getAvailableUrl([$conversion]),
            srcset: $srcset === '' ? null : $srcset,
        );
    }

    /**
     * @return Collection<int, ResponsiveImageData>
     */
    public static function forProductGallery(?Product $product): Collection
    {
        if ($product === null) {
            return collect();
        }

        $thumbnail = $product->getFirstMedia('thumbnail');

        return collect($thumbnail ? [$thumbnail] : [])
            ->merge($product->getGalleryMedia())
            ->map(self::forMedia(...))
            ->filter()
            ->values();
    }
}
