<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\StockStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Type;
use App\Services\Catalog\ProductPricingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_product_exposes_price_specs_and_available_stock(): void
    {
        $product = Product::factory()
            ->active()
            ->create([
                'price_in_fils' => 12_345,
                'stock' => 100,
                'reserved_stock' => 30,
                'specs_en' => ['Color' => 'Black'],
                'specs_ar' => ['Color' => 'أسود'],
            ]);

        static::assertSame(12.345, $product->price_in_fils->toAmount());
        static::assertSame(70, $product->getAvailableStock());

        app()->setLocale('en');
        static::assertSame(['Color' => 'Black'], $product->specs);

        app()->setLocale('ar');
        static::assertSame(['Color' => 'أسود'], $product->specs);

        app()->setLocale('en');
    }

    public function test_product_exposes_gallery_media(): void
    {
        $product = Product::factory()->create();

        static::assertTrue($product->getGalleryMedia()->isEmpty());
    }

    public function test_low_stock_state_creates_a_low_stock_product(): void
    {
        $product = Product::factory()->lowStock()->create();

        static::assertTrue($product->isLowOnStock());
        static::assertGreaterThanOrEqual(0, $product->getAvailableStock());
        static::assertLessThanOrEqual(
            $product->low_stock_threshold,
            $product->getAvailableStock(),
        );
    }

    public function test_with_specs_state_sets_localized_spec_values(): void
    {
        $product = Product::factory()
            ->withSpecs(
                ['Color' => 'Black', 'Warranty' => '1 Year'],
                ['Color' => 'أسود', 'Warranty' => 'سنة واحدة'],
            )
            ->create();

        static::assertSame(
            ['Color' => 'Black', 'Warranty' => '1 Year'],
            $product->specs_en,
        );
        static::assertSame(
            ['Color' => 'أسود', 'Warranty' => 'سنة واحدة'],
            $product->specs_ar,
        );
    }

    public function test_discounted_state_attaches_an_active_discount(): void
    {
        $product = Product::factory()
            ->active()
            ->discounted(value: 10)
            ->create(['price_in_fils' => 20_000]);

        static::assertCount(1, $product->discounts);

        static::assertSame(
            18_000,
            app(ProductPricingService::class)->getCurrentPrice($product),
        );
    }

    public function test_with_gallery_images_state_creates_gallery_media(): void
    {
        Storage::fake('public');

        $product = Product::factory()->withGalleryImages(2)->create();

        static::assertCount(2, $product->getGalleryMedia());

        static::assertTrue(
            $product->getGalleryMedia()->every(static fn ($media) => $media->collection_name === 'gallery'),
        );
    }

    public function test_discounted_scope_and_method_follow_active_discounts(): void
    {
        $byType = $this->createIsolatedProduct();
        Discount::factory()
            ->percentage(10)
            ->create()
            ->productTypes()
            ->attach($byType->type);

        $byCategory = $this->createIsolatedProduct();
        Discount::factory()
            ->percentage(10)
            ->create()
            ->categories()
            ->attach($byCategory->category);

        $byBrand = $this->createIsolatedProduct();
        Discount::factory()
            ->percentage(10)
            ->create()
            ->brands()
            ->attach($byBrand->brand);

        $expired = $this->createIsolatedProduct();
        Discount::factory()
            ->percentage(10)
            ->expired()
            ->create()
            ->products()
            ->attach($expired);

        $plain = $this->createIsolatedProduct();

        foreach ([$byType, $byCategory, $byBrand] as $product) {
            static::assertTrue($product->isDiscounted());
        }

        foreach ([$expired, $plain] as $product) {
            static::assertFalse($product->isDiscounted());
        }

        static::assertSame(3, Product::query()->discounted()->count());
    }

    public function test_stock_status_reflects_stock_levels(): void
    {
        $inStock = Product::factory()
            ->active()
            ->create([
                'stock' => 10,
                'reserved_stock' => 0,
                'low_stock_threshold' => 5,
            ]);

        $lowStock = Product::factory()
            ->active()
            ->create([
                'stock' => 2,
                'reserved_stock' => 0,
                'low_stock_threshold' => 5,
            ]);

        $outOfStock = Product::factory()
            ->active()
            ->create([
                'stock' => 3,
                'reserved_stock' => 3,
                'low_stock_threshold' => 5,
            ]);

        static::assertSame(StockStatus::IN_STOCK, $inStock->stockStatus());
        static::assertSame(StockStatus::LOW_STOCK, $lowStock->stockStatus());
        static::assertSame(
            StockStatus::OUT_OF_STOCK,
            $outOfStock->stockStatus(),
        );
    }

    private function createIsolatedProduct(): Product
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $type = Type::factory()->create(['category_id' => $category->id]);
        $brand = Brand::factory()->create();

        return Product::factory()
            ->active()
            ->create([
                'type_id' => $type->id,
                'brand_id' => $brand->id,
            ]);
    }
}
