<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Type;
use App\Services\Catalog\CategoryTreeService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CategoryBrowsingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_category_page_shows_breadcrumbs_description_subcategories_and_count(): void
    {
        $category = Category::factory()
            ->active()
            ->create([
                'parent_id' => null,
                'description_en' => 'Top of the line office furniture.',
            ]);

        $child = Category::factory()
            ->active()
            ->create([
                'parent_id' => $category->id,
                'name_en' => 'Office Chairs',
            ]);

        $this
            ->get(route('category.show', $category->slug))
            ->assertOk()
            ->assertSee(__('catalog.breadcrumbs.home'))
            ->assertSee($category->name)
            ->assertSee('Top of the line office furniture.')
            ->assertSee(__('catalog.categories.subcategories'))
            ->assertSee($child->name)
            ->assertSee(trans_choice('catalog.categories.count', 0));
    }

    public function test_a_deep_category_page_shows_every_ancestor_in_its_breadcrumbs(): void
    {
        $root = Category::factory()
            ->active()
            ->create(['parent_id' => null, 'name_en' => 'Components']);
        $child = Category::factory()
            ->active()
            ->create(['parent_id' => $root->id, 'name_en' => 'Graphics Cards']);
        $grandchild = Category::factory()
            ->active()
            ->create(['parent_id' => $child->id, 'name_en' => 'Nvidia Cards']);

        $response = $this
            ->get(route('category.show', $grandchild->slug))
            ->assertOk();

        $breadcrumbs = Str::between(
            (string) $response->getContent(),
            '<nav class="breadcrumbs',
            '</nav>',
        );

        $rootLink = route('category.show', $root->slug);
        $childLink = route('category.show', $child->slug);

        static::assertStringContainsString($rootLink, $breadcrumbs);
        static::assertStringContainsString($childLink, $breadcrumbs);
        static::assertStringContainsString($grandchild->name, $breadcrumbs);

        static::assertLessThan(
            strpos($breadcrumbs, $childLink),
            strpos($breadcrumbs, $rootLink),
        );
    }

    public function test_the_ancestor_chain_is_resolved_in_one_query(): void
    {
        $root = Category::factory()->active()->create(['parent_id' => null]);
        $child = Category::factory()->active()->create(['parent_id' => $root->id]);
        $grandchild = Category::factory()
            ->active()
            ->create(['parent_id' => $child->id]);

        $category = Category::query()->whereKey($grandchild->id)->firstOrFail();

        $categoryTreeService = app(CategoryTreeService::class);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $ancestors = $categoryTreeService->ancestors($category);

        $queries = collect(DB::getQueryLog());

        DB::disableQueryLog();

        static::assertCount(1, $queries, 'The ancestor chain should be read in one query.');
        static::assertSame([$root->id, $child->id], $ancestors->modelKeys());
        static::assertFalse($category->relationLoaded('parent'));
    }

    public function test_category_page_includes_products_from_descendant_categories(): void
    {
        $parent = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $child = Category::factory()
            ->active()
            ->create(['parent_id' => $parent->id]);

        $parentProduct = $this->createProduct($this->createType($parent), [
            'name' => 'Parent Level Product',
        ]);
        $childProduct = $this->createProduct($this->createType($child), [
            'name' => 'Child Level Product',
        ]);

        $otherCategory = Category::factory()
            ->active()
            ->create([
                'parent_id' => null,
            ]);
        $otherProduct = $this->createProduct(
            $this->createType($otherCategory),
            ['name' => 'Unrelated Product'],
        );

        $this
            ->get(route('category.show', $parent->slug))
            ->assertOk()
            ->assertSee($parentProduct->name)
            ->assertSee($childProduct->name)
            ->assertDontSee($otherProduct->name);
    }

    public function test_category_page_filters_by_brand(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $type = $this->createType($category);

        $zenith = Brand::factory()->create(['name_en' => 'Zenith']);
        $orbit = Brand::factory()->create(['name_en' => 'Orbit']);

        $zenithProduct = $this->createProduct($type, [
            'name' => 'Zenith Powered Product',
            'brand_id' => $zenith->id,
        ]);
        $orbitProduct = $this->createProduct($type, [
            'name' => 'Orbit Powered Product',
            'brand_id' => $orbit->id,
        ]);

        $this
            ->get(
                route('category.show', [
                    'category' => $category->slug,
                    'brand' => [$zenith->id],
                ]),
            )
            ->assertOk()
            ->assertSee($zenithProduct->name)
            ->assertDontSee($orbitProduct->name);
    }

    public function test_category_page_filters_by_price_range(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $type = $this->createType($category);

        $cheap = $this->createProduct($type, [
            'name' => 'Budget Friendly Product',
            'price_in_fils' => 1_000,
        ]);
        $expensive = $this->createProduct($type, [
            'name' => 'Premium Priced Product',
            'price_in_fils' => 9_000,
        ]);

        $this
            ->get(
                route('category.show', [
                    'category' => $category->slug,
                    'price_min' => 0.5,
                    'price_max' => 2,
                ]),
            )
            ->assertOk()
            ->assertSee($cheap->name)
            ->assertDontSee($expensive->name);
    }

    public function test_category_page_filters_by_availability(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $type = $this->createType($category);

        $inStock = $this->createProduct($type, [
            'name' => 'Available Product',
            'stock' => 10,
            'reserved_stock' => 0,
        ]);
        $outOfStock = $this->createProduct($type, [
            'name' => 'Unavailable Product',
            'stock' => 0,
            'reserved_stock' => 0,
        ]);

        $this
            ->get(
                route('category.show', [
                    'category' => $category->slug,
                    'availability' => 'in_stock',
                ]),
            )
            ->assertOk()
            ->assertSee($inStock->name)
            ->assertDontSee($outOfStock->name);
    }

    public function test_category_page_filters_by_discounted_products(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $type = $this->createType($category);

        $discounted = Product::factory()
            ->active()
            ->discounted()
            ->create([
                'type_id' => $type->id,
                'name' => 'Discounted Product',
            ]);

        $regular = $this->createProduct($type, [
            'name' => 'Regular Product',
        ]);

        $this
            ->get(
                route('category.show', [
                    'category' => $category->slug,
                    'discounted' => 1,
                ]),
            )
            ->assertOk()
            ->assertSee($discounted->name)
            ->assertDontSee($regular->name);
    }

    public function test_category_page_discounted_filter_matches_type_category_and_brand_discounts(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);

        $typeWithDiscount = $this->createType($category);
        $typePlain = $this->createType($category);
        $typeWithBrand = $this->createType($category);

        $brand = Brand::factory()->create();

        $productByType = $this->createProduct($typeWithDiscount, [
            'name' => 'Type Discounted Product',
        ]);
        $productByCategory = $this->createProduct($typePlain, [
            'name' => 'Category Discounted Product',
        ]);
        $productByBrand = $this->createProduct($typeWithBrand, [
            'name' => 'Brand Discounted Product',
            'brand_id' => $brand->id,
        ]);

        Discount::factory()
            ->percentage(10)
            ->create()
            ->productTypes()
            ->attach($typeWithDiscount);
        Discount::factory()
            ->percentage(10)
            ->create()
            ->categories()
            ->attach($category);
        Discount::factory()->percentage(10)->create()->brands()->attach($brand);

        $this
            ->get(
                route('category.show', [
                    'category' => $category->slug,
                    'discounted' => 1,
                ]),
            )
            ->assertOk()
            ->assertSee($productByType->name)
            ->assertSee($productByCategory->name)
            ->assertSee($productByBrand->name);
    }

    public function test_category_page_product_cards_link_to_product_pages(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $type = $this->createType($category);

        $product = $this->createProduct($type, ['name' => 'Linked Product']);

        $this
            ->get(route('category.show', $category->slug))
            ->assertOk()
            ->assertSee(route('products.show', $product->slug), false);
    }

    public function test_category_page_combines_multiple_filters(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $type = $this->createType($category);

        $brand = Brand::factory()->create();
        $otherBrand = Brand::factory()->create();

        $matching = $this->createProduct($type, [
            'name' => 'Matching Combined Product',
            'brand_id' => $brand->id,
            'stock' => 5,
            'reserved_stock' => 0,
        ]);
        $wrongAvailability = $this->createProduct($type, [
            'name' => 'Sold Out Combined Product',
            'brand_id' => $brand->id,
            'stock' => 0,
            'reserved_stock' => 0,
        ]);
        $wrongBrand = $this->createProduct($type, [
            'name' => 'Other Brand Combined Product',
            'brand_id' => $otherBrand->id,
            'stock' => 5,
            'reserved_stock' => 0,
        ]);

        $this
            ->get(
                route('category.show', [
                    'category' => $category->slug,
                    'brand' => [$brand->id],
                    'availability' => 'in_stock',
                ]),
            )
            ->assertOk()
            ->assertSee($matching->name)
            ->assertDontSee($wrongAvailability->name)
            ->assertDontSee($wrongBrand->name);
    }

    public function test_category_page_sorts_by_price(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $type = $this->createType($category);

        $cheap = $this->createProduct($type, [
            'name' => 'Cheap Sorted Product',
            'price_in_fils' => 1_000,
        ]);
        $expensive = $this->createProduct($type, [
            'name' => 'Expensive Sorted Product',
            'price_in_fils' => 9_000,
        ]);

        $this
            ->get(
                route('category.show', [
                    'category' => $category->slug,
                    'sort' => 'price_asc',
                ]),
            )
            ->assertOk()
            ->assertSeeInOrder([$cheap->name, $expensive->name]);

        $this
            ->get(
                route('category.show', [
                    'category' => $category->slug,
                    'sort' => 'price_desc',
                ]),
            )
            ->assertOk()
            ->assertSeeInOrder([$expensive->name, $cheap->name]);
    }

    public function test_category_page_sorts_by_name_and_newest(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $type = $this->createType($category);

        $alpha = $this->createProduct($type, [
            'name' => 'Alpha Sorted Product',
        ]);
        $omega = $this->createProduct($type, [
            'name' => 'Omega Sorted Product',
        ]);

        $alpha->created_at = now()->subDays(2);
        $alpha->save();

        $omega->created_at = now();
        $omega->save();

        $this
            ->get(
                route('category.show', [
                    'category' => $category->slug,
                    'sort' => 'name',
                ]),
            )
            ->assertOk()
            ->assertSeeInOrder([$alpha->name, $omega->name]);

        $this
            ->get(
                route('category.show', [
                    'category' => $category->slug,
                    'sort' => 'newest',
                ]),
            )
            ->assertOk()
            ->assertSeeInOrder([$omega->name, $alpha->name]);
    }

    public function test_category_page_offers_the_next_batch_with_the_filters_kept(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $type = $this->createType($category);
        $brand = Brand::factory()->create();

        Product::factory()
            ->active()
            ->count(13)
            ->create([
                'type_id' => $type->id,
                'brand_id' => $brand->id,
            ]);

        $this
            ->get(
                route('category.show', [
                    'category' => $category->slug,
                    'brand' => [$brand->id],
                ]),
            )
            ->assertOk()
            // The next batch is read in place, and the way to it is a link that
            // carries the filters the listing is narrowed by.
            ->assertSee(__('catalog.pagination.load_more'))
            ->assertSee('brand%5B0%5D='.$brand->id, false)
            ->assertSee('page=2', false);
    }

    public function test_category_page_renders_empty_state_without_products(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);

        $this
            ->get(route('category.show', $category->slug))
            ->assertOk()
            ->assertSee(__('catalog.empty.title'))
            ->assertSee(__('catalog.categories.empty'));
    }

    public function test_categories_index_lists_products_under_each_category(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $type = $this->createType($category);

        $this->createProduct($type, ['name' => 'Counted Product']);

        $this
            ->get(route('category.index'))
            ->assertOk()
            ->assertSee('Counted Product');
    }

    public function test_breadcrumbs_follow_the_category_tree(): void
    {
        $root = Category::factory()
            ->active()
            ->create([
                'parent_id' => null,
                'name_en' => 'Root Catalog',
            ]);
        $parent = Category::factory()
            ->active()
            ->create([
                'parent_id' => $root->id,
                'name_en' => 'Parent Catalog',
            ]);
        $child = Category::factory()
            ->active()
            ->create([
                'parent_id' => $parent->id,
                'name_en' => 'Child Catalog',
            ]);

        $this
            ->get(route('category.show', $child->slug))
            ->assertOk()
            ->assertSeeInOrder([$root->name, $parent->name, $child->name]);
    }

    public function test_descendant_ids_cache_is_invalidated_when_the_tree_changes(): void
    {
        $parent = Category::factory()
            ->active()
            ->create(['parent_id' => null]);

        $categoryTreeService = app(CategoryTreeService::class);

        static::assertSame([$parent->id], $categoryTreeService->descendantIds($parent));

        $child = Category::factory()
            ->active()
            ->create(['parent_id' => $parent->id]);

        static::assertSame([$parent->id, $child->id], $categoryTreeService->descendantIds($parent));

        $child->delete();

        static::assertSame([$parent->id], $categoryTreeService->descendantIds($parent));
    }

    public function test_product_cards_pin_the_stock_badge_to_the_inline_end(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $type = $this->createType($category);

        $this->createProduct($type, ['name' => 'Badged Product']);

        $this
            ->get(route('category.show', $category->slug))
            ->assertOk()
            ->assertSee('product-card__badges', false)
            ->assertSee(__('catalog.stock.in_stock'));

        // Logical inset so the badge flips sides in RTL.
        $css = file_get_contents(
            resource_path('css/components/product-card.css'),
        );
        static::assertStringContainsString(
            'inset-inline-end: var(--space-400)',
            $css,
        );
    }

    public function test_category_index_shows_each_category_as_a_section_with_products_and_subcategory_buttons(): void
    {
        $parent = Category::factory()
            ->active()
            ->create([
                'parent_id' => null,
                'name_en' => 'Audio Gear',
            ]);
        $child = Category::factory()
            ->active()
            ->create([
                'parent_id' => $parent->id,
                'name_en' => 'Headphones',
            ]);

        // Products from a descendant category appear in the parent section.
        $product = $this->createProduct($this->createType($child), [
            'name' => 'Studio Monitor Speaker',
        ]);

        $this
            ->get(route('category.index'))
            ->assertOk()
            ->assertSee('page-header__title', false)
            ->assertSee('section-title--sm', false)
            ->assertSee('category-section', false)
            ->assertSee($parent->name)
            ->assertSee($child->name)
            ->assertSee(route('category.show', $parent->slug), false)
            ->assertSee(route('category.show', $child->slug), false)
            ->assertSee($product->name)
            // The children are their own chip row under the heading, not middle
            // content squeezed into the section header.
            ->assertSee('<nav class="subcategories"', false)
            ->assertSee('subcategories__list', false)
            ->assertDontSee('section-header__middle', false)
            ->assertSee(__('catalog.categories.see_all'));
    }

    private function createType(Category $category): Type
    {
        return Type::factory()->create(['category_id' => $category->id]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createProduct(Type $type, array $attributes = []): Product
    {
        return Product::factory()
            ->active()
            ->create(array_merge(['type_id' => $type->id], $attributes));
    }
}
