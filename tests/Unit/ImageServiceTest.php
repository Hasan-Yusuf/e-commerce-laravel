<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Product;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/**
 * Which file the storefront serves for a piece of media: the WebP version when
 * its conversion was generated, and the file as it was stored when it was not.
 */
class ImageServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_media_with_a_generated_webp_conversion_is_served_as_webp(): void
    {
        Storage::fake('public');

        $media = $this->mediaWithThumbnail();

        $image = ImageService::forMedia($media);

        static::assertNotNull($image);
        static::assertSame($media->getUrl('webp'), $image->src);
        static::assertStringContainsString('.webp', (string) $image->srcset);
    }

    public function test_a_media_without_a_webp_conversion_falls_back_to_the_stored_file(): void
    {
        Storage::fake('public');

        $media = $this->mediaWithThumbnail();
        $media->markAsConversionNotGenerated('webp');

        $image = ImageService::forMedia($media);

        static::assertNotNull($image);
        static::assertSame($media->getUrl(), $image->src);
        static::assertStringNotContainsString('.webp', (string) $image->srcset);
    }

    /**
     * The service holds no state, so it is reached statically; an instance is
     * still a usable way to reach it and resolves the same image.
     */
    public function test_an_instance_resolves_the_same_image_as_a_static_call(): void
    {
        Storage::fake('public');

        $media = $this->mediaWithThumbnail();

        $service = new ImageService;

        static::assertEquals(
            ImageService::forMedia($media),
            $service->forMedia($media),
        );
    }

    private function mediaWithThumbnail(): Media
    {
        /** @var Media $media */
        return Product::factory()
            ->withThumbnailImage()
            ->create()
            ->getFirstMedia('thumbnail');
    }
}
