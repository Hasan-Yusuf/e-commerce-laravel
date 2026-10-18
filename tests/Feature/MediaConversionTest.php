<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\MediaLibrary\Conversions\ConversionCollection;
use Tests\TestCase;

/**
 * Images are stored twice: the file the administrator chose, and the WebP
 * version of it the storefront serves.
 *
 * The conversion is registered on every model that takes images, so an upload
 * through the panel can be asserted from the media record it produced.
 */
class MediaConversionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_an_image_uploaded_through_the_panel_is_stored_as_webp(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $type = Type::factory()->create();

        // No spec definitions, so the product form has no spec values to fill.
        $type->specDefinitions()->delete();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Converted Product',
                'slug' => 'converted-product',
                'sku' => 'CONV-1',
                'description_en' => 'An English description.',
                'price_in_fils' => '12.5',
                'cost_price_in_fils' => '8.0',
                'Thumbnail' => UploadedFile::fake()->image('chosen.png', 800, 600),
                'type_id' => $type->id,
                'stock' => 10,
                'low_stock_threshold' => 5,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $media = Product::query()->sole()->getFirstMedia('thumbnail');

        static::assertNotNull($media);

        // The file that was uploaded is kept as it was...
        static::assertStringEndsWith('.png', $media->getPath());

        // ...and the WebP version the storefront asks for was generated beside it.
        static::assertTrue($media->hasGeneratedConversion('webp'));
        static::assertStringEndsWith('.webp', $media->getPath('webp'));
        static::assertSame('image/webp', mime_content_type($media->getPath('webp')));
        static::assertStringContainsString('webp', $media->getUrl('webp'));
    }

    public function test_a_category_image_is_kept_in_the_collection_the_storefront_reads(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name_en' => 'Laptops',
                'name_ar' => 'Laptops AR',
                'slug' => 'laptops',
                'description_en' => 'Portable machines.',
                'Image' => UploadedFile::fake()->image('category.png', 600, 400),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = Category::query()->sole();
        $media = $category->getFirstMedia('thumbnail');

        static::assertNotNull($media);
        static::assertTrue($media->hasGeneratedConversion('webp'));
        static::assertSame(1, $media->getKey() ? $category->getMedia('thumbnail')->count() : 0);
    }

    public function test_the_storefront_serves_the_webp_version(): void
    {
        Storage::fake('public');

        $category = Category::factory()->active()->create(['parent_id' => null]);
        $type = Type::factory()->create(['category_id' => $category->id]);

        $product = Product::factory()
            ->active()
            ->withThumbnailImage()
            ->create(['type_id' => $type->id]);

        $media = $product->getFirstMedia('thumbnail');

        static::assertNotNull($media);
        static::assertTrue($media->hasGeneratedConversion('webp'));

        $this
            ->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee($media->getAvailableUrl(['webp']), false);
    }

    public function test_a_webp_conversion_keeps_the_transparency_it_was_uploaded_with(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $type = Type::factory()->create();
        $type->specDefinitions()->delete();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Transparent Product',
                'slug' => 'transparent-product',
                'sku' => 'CONV-2',
                'description_en' => 'An English description.',
                'price_in_fils' => '12.5',
                'cost_price_in_fils' => '8.0',
                'Thumbnail' => $this->transparentPng(),
                'type_id' => $type->id,
                'stock' => 10,
                'low_stock_threshold' => 5,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $media = Product::query()->sole()->getFirstMedia('thumbnail');

        static::assertTrue($media->hasGeneratedConversion('webp'));

        $webp = imagecreatefromwebp($media->getPath('webp'));

        // The canvas was filled with a fully transparent white and only the
        // middle square was painted, so a corner is a hole in the image and the
        // middle is not: the conversion has to say the same.
        static::assertGreaterThan(
            100,
            imagecolorsforindex($webp, imagecolorat($webp, 0, 0))['alpha'],
        );
        static::assertLessThan(
            20,
            imagecolorsforindex($webp, imagecolorat($webp, 20, 20))['alpha'],
        );

        imagedestroy($webp);
    }

    public function test_a_brand_logo_uploaded_through_the_panel_is_stored_as_webp(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        Livewire::test(CreateBrand::class)
            ->fillForm([
                'name_en' => 'Acme',
                'logo_light' => UploadedFile::fake()->image('black.png', 400, 400),
                'logo_dark' => UploadedFile::fake()->image('white.png', 400, 400),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $brand = Brand::query()->sole();

        foreach (['logo_light', 'logo_dark'] as $collection) {
            $media = $brand->getFirstMedia($collection);

            static::assertNotNull($media);
            static::assertTrue($media->hasGeneratedConversion('webp'));
            static::assertStringEndsWith('.webp', $media->getPath('webp'));
        }
    }

    /**
     * The conversion is left for the queue worker: the application runs a worker,
     * so the WebP file is produced shortly after the upload rather than during
     * the request that stored the image. The storefront falls back to the
     * uploaded file until the conversion exists.
     */
    public function test_the_webp_conversion_is_queued_for_the_worker(): void
    {
        Storage::fake('public');

        $media = Product::factory()
            ->withThumbnailImage()
            ->create()
            ->getFirstMedia('thumbnail');

        $conversion = ConversionCollection::createForMedia($media)
            ->first(static fn ($conversion): bool => $conversion->getName() === 'webp');

        static::assertNotNull($conversion);
        static::assertTrue($conversion->shouldBeQueued());
    }

    /**
     * A PNG whose corners are see-through and whose middle is an opaque square,
     * so a conversion can be asked which pixels it kept transparent.
     */
    private function transparentPng(): UploadedFile
    {
        $image = imagecreatetruecolor(40, 40);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        imagefilledrectangle(
            $image,
            0,
            0,
            39,
            39,
            imagecolorallocatealpha($image, 255, 255, 255, 127),
        );
        imagefilledrectangle(
            $image,
            10,
            10,
            29,
            29,
            imagecolorallocatealpha($image, 255, 0, 0, 0),
        );

        ob_start();
        imagepng($image);
        $contents = (string) ob_get_clean();

        imagedestroy($image);

        return UploadedFile::fake()->createWithContent(
            'transparent.png',
            $contents,
        );
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    }
}
