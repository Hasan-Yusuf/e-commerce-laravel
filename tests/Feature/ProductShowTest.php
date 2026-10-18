<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Type;
use App\Models\TypeSpec;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductShowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_product_page_renders_detail_specs_description_stock_and_breadcrumbs(): void
    {
        $category = Category::factory()
            ->active()
            ->create([
                'parent_id' => null,
                'name_en' => 'Workstations',
            ]);

        $type = Type::factory()->create([
            'category_id' => $category->id,
        ]);

        $type->specDefinitions()->delete();

        TypeSpec::factory()->for($type)->create([
            'name_en' => 'Color',
            'name_ar' => 'اللون',
            'sort_order' => 0,
        ]);

        $brand = Brand::factory()->create(['name_en' => 'RigWorks']);

        $product = Product::factory()
            ->active()
            ->create([
                'type_id' => $type->id,
                'brand_id' => $brand->id,
                'name' => 'RigWorks Titan Workstation',
                'sku' => 'RIG-TITAN-01',
                'description_en' => 'A powerful workstation for creators.',
                'specs_en' => ['Color' => 'Midnight Black'],
                'specs_ar' => ['اللون' => 'أسود'],
                'stock' => 20,
                'reserved_stock' => 0,
            ]);

        $this
            ->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('RigWorks')
            ->assertSee('RIG-TITAN-01')
            ->assertSee('A powerful workstation for creators.')
            ->assertSee('Color')
            ->assertSee('Midnight Black')
            ->assertSee(__('catalog.stock.in_stock'))
            ->assertSee(__('catalog.breadcrumbs.home'))
            ->assertSee('Workstations')
            ->assertSee(__('catalog.product.specs_title'))
            ->assertSee('product-detail__grid', false)
            ->assertSee('product-detail__stock', false)
            ->assertSee('product-detail__price', false)
            ->assertSee('product-detail__wishlist', false)
            ->assertDontSee('info-block__body', false);
    }

    public function test_product_page_shows_original_and_current_price_with_discount_badge(): void
    {
        $product = $this->createProduct([
            'name' => 'Discounted Workstation',
            'price_in_fils' => 10_000,
        ]);

        $discount = Discount::factory()->percentage(25)->create();
        $discount->products()->attach($product);

        $this
            ->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('7.5 BHD')
            ->assertSee('10 BHD')
            ->assertSee('-25%')
            ->assertSee('product-price__original', false)
            ->assertSee('product-price__badge', false);
    }

    public function test_product_page_shows_only_the_normal_price_without_a_discount(): void
    {
        $product = $this->createProduct([
            'name' => 'Undiscounted Workstation',
            'price_in_fils' => 10_000,
        ]);

        $this
            ->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('10 BHD')
            ->assertDontSee('product-price__original', false)
            ->assertDontSee('product-price__badge', false);
    }

    public function test_product_page_shows_low_and_out_of_stock_badges(): void
    {
        $lowStock = $this->createProduct([
            'name' => 'Low Stock Workstation',
            'stock' => 2,
            'reserved_stock' => 0,
            'low_stock_threshold' => 5,
        ]);

        $this
            ->get(route('products.show', $lowStock->slug))
            ->assertOk()
            ->assertSee(
                trans_choice('catalog.stock.low_stock', 2, ['count' => 2]),
            );

        $outOfStock = $this->createProduct([
            'name' => 'Out Of Stock Workstation',
            'stock' => 0,
            'reserved_stock' => 0,
        ]);

        $this
            ->get(route('products.show', $outOfStock->slug))
            ->assertOk()
            ->assertSee(__('catalog.stock.out_of_stock'));
    }

    public function test_product_page_shows_related_and_recommended_products(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $type = Type::factory()->create(['category_id' => $category->id]);

        $product = Product::factory()
            ->active()
            ->create([
                'type_id' => $type->id,
                'name' => 'Primary Product',
            ]);

        $related = Product::factory()
            ->active()
            ->create([
                'type_id' => $type->id,
                'name' => 'Same Type Sibling',
            ]);

        $recommended = Product::factory()
            ->active()
            ->featured()
            ->create([
                'type_id' => Type::factory()->create([
                    'category_id' => $category->id,
                ])->id,
                'name' => 'Featured Recommendation',
            ]);

        $this
            ->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee(__('catalog.product.related_title'))
            ->assertSee($related->name)
            ->assertSee(__('catalog.product.recommended_title'))
            ->assertSee($recommended->name)
            ->assertDontSee($product->name.'</h3>', false);
    }

    public function test_product_page_renders_the_gallery_media(): void
    {
        Storage::fake('public');

        $product = $this->createProduct(
            ['name' => 'Gallery Workstation'],
            withGalleryImages: 2,
        );

        $this
            ->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('data-gallery-main', false)
            ->assertSee('data-gallery-thumb', false);
    }

    public function test_inactive_product_returns_not_found(): void
    {
        $product = $this->createProduct([
            'name' => 'Inactive Workstation',
            'activated_at' => now()->addDay(),
        ]);

        $this->get(route('products.show', $product->slug))->assertNotFound();
    }

    public function test_product_breadcrumbs_include_the_category_ancestry(): void
    {
        $root = Category::factory()
            ->active()
            ->create([
                'parent_id' => null,
                'name_en' => 'Root Ancestor Category',
            ]);
        $parent = Category::factory()
            ->active()
            ->create([
                'parent_id' => $root->id,
                'name_en' => 'Parent Ancestor Category',
            ]);
        $child = Category::factory()
            ->active()
            ->create([
                'parent_id' => $parent->id,
                'name_en' => 'Child Ancestor Category',
            ]);

        $type = Type::factory()->create(['category_id' => $child->id]);

        $product = Product::factory()
            ->active()
            ->create([
                'type_id' => $type->id,
                'brand_id' => Brand::factory()->create()->id,
                'name' => 'Ancestry Product',
            ]);

        $this
            ->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSeeInOrder([$root->name, $parent->name, $child->name]);
    }

    public function test_product_page_renders_typed_spec_values_with_their_unit_and_yes_no(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);

        $type = Type::factory()->create(['category_id' => $category->id]);
        $type->specDefinitions()->delete();

        TypeSpec::factory()
            ->for($type)
            ->number('GB')
            ->create([
                'name_en' => 'Memory',
                'name_ar' => 'الذاكرة',
                'sort_order' => 0,
            ]);

        TypeSpec::factory()
            ->for($type)
            ->boolean()
            ->create([
                'name_en' => 'RGB',
                'name_ar' => 'إضاءة RGB',
                'sort_order' => 1,
            ]);

        $product = $this->createProduct([
            'type_id' => $type->id,
            'specs_en' => ['Memory' => 16, 'RGB' => true],
            'specs_ar' => ['الذاكرة' => 16, 'إضاءة RGB' => false],
        ]);

        $this
            ->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('16 GB')
            ->assertSee(__('catalog.product.spec_yes'));
    }

    public function test_product_page_shows_a_brand_logo_per_colour_scheme(): void
    {
        Storage::fake('public');

        $brand = Brand::factory()->create(['name_en' => 'RigWorks']);

        $brand->addMedia(UploadedFile::fake()->image('light.png', 240, 80))->toMediaCollection('logo_light');

        $brand->addMedia(UploadedFile::fake()->image('dark.png', 240, 80))->toMediaCollection('logo_dark');

        $product = $this->createProduct([
            'brand_id' => $brand->id,
            'name' => 'RigWorks Titan Workstation',
        ]);

        $this
            ->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('brand-logo--for-light', false)
            ->assertSee('brand-logo--for-dark', false)
            ->assertSee('alt="RigWorks"', false);
    }

    public function test_product_page_names_the_brand_where_a_scheme_has_no_logo(): void
    {
        Storage::fake('public');

        $brand = Brand::factory()->create(['name_en' => 'RigWorks']);

        // Only a dark-scheme logo, so the name stands in on the light scheme.
        $brand->addMedia(UploadedFile::fake()->image('dark.png', 240, 80))->toMediaCollection('logo_dark');

        $product = $this->createProduct([
            'brand_id' => $brand->id,
            'name' => 'RigWorks Titan Workstation',
        ]);

        $this
            ->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('brand-logo--for-dark', false)
            ->assertDontSee('brand-logo--for-light', false)
            ->assertSee('brand-name--for-light', false);
    }

    public function test_product_page_names_the_brand_when_it_has_no_logo(): void
    {
        $brand = Brand::factory()->create(['name_en' => 'RigWorks']);

        $product = $this->createProduct([
            'brand_id' => $brand->id,
            'name' => 'RigWorks Titan Workstation',
        ]);

        $this
            ->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('brand-name', false)
            ->assertDontSee('brand-logo', false);
    }

    public function test_the_specs_table_stacks_into_readable_rows_on_narrow_screens(): void
    {
        $css = file_get_contents(
            resource_path('css/features/product-page.css'),
        );

        // Long values wrap rather than pushing the table past the screen, and a
        // line divides one spec from the next at every width.
        static::assertStringContainsString('table-layout: fixed;', $css);
        static::assertStringContainsString(
            '.specs-table tbody tr + tr > * {',
            $css,
        );

        // Under the narrow-screen query the value takes the full width below
        // its name, and the row carries the dividing line itself.
        static::assertMatchesRegularExpression(
            '/@media \(max-width: 599\.98px\) \{(?:(?!@media).)*?\.specs-table tbody tr \+ tr \{/s',
            $css,
        );
        static::assertMatchesRegularExpression(
            '/@media \(max-width: 599\.98px\) \{(?:(?!@media).)*?\.specs-table__value \{(?:(?!@media).)*?display: block;/s',
            $css,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createProduct(
        array $attributes = [],
        int $withGalleryImages = 0,
    ): Product {
        $type = $attributes['type_id'] ?? null;

        if ($type === null) {
            $category = Category::factory()
                ->active()
                ->create([
                    'parent_id' => null,
                ]);
            $type = Type::factory()->create([
                'category_id' => $category->id,
            ])->id;
        }

        $factory = Product::factory();

        if ($withGalleryImages > 0) {
            $factory = $factory->withGalleryImages($withGalleryImages);
        }

        return $factory->active()->create(array_merge(['type_id' => $type], $attributes));
    }
}
