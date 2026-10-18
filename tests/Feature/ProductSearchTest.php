<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Number;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_search_matches_product_name(): void
    {
        $matching = Product::factory()
            ->active()
            ->create(['name' => 'Orbit Mechanical Keyboard']);
        $other = Product::factory()
            ->active()
            ->create(['name' => 'Zenith Ultrawide Monitor']);

        $this
            ->get(route('products.index', ['q' => 'keyboard']))
            ->assertOk()
            ->assertSee($matching->name)
            ->assertDontSee($other->name);
    }

    public function test_search_matches_meta_name(): void
    {
        $matching = Product::factory()
            ->active()
            ->create([
                'name' => 'Untitled Product',
                'meta_name' => 'Silent Switch Keyboard',
            ]);
        $other = Product::factory()
            ->active()
            ->create([
                'name' => 'Other Product',
                'meta_name' => 'Monitor Stand',
            ]);

        $this
            ->get(route('products.index', ['q' => 'Silent Switch']))
            ->assertOk()
            ->assertSee($matching->name)
            ->assertDontSee($other->name);
    }

    public function test_search_matches_sku(): void
    {
        $matching = Product::factory()
            ->active()
            ->create([
                'name' => 'Gaming Mouse',
                'sku' => 'SKU-KEYBOARD-42',
            ]);
        $other = Product::factory()
            ->active()
            ->create([
                'name' => 'Office Mouse',
                'sku' => 'SKU-MONITOR-99',
            ]);

        $this
            ->get(route('products.index', ['q' => 'KEYBOARD-42']))
            ->assertOk()
            ->assertSee($matching->name)
            ->assertDontSee($other->name);
    }

    public function test_search_matches_brand_name_in_both_locales(): void
    {
        $brand = Brand::factory()->create([
            'name_en' => 'Zenith',
            'name_ar' => 'زينيث',
        ]);
        $brandProduct = Product::factory()
            ->active()
            ->create([
                'name' => 'Branded Product',
                'brand_id' => $brand->id,
                'sku' => 'ZEN-0001',
            ]);

        $otherBrand = Brand::factory()->create([
            'name_en' => 'Orbit',
            'name_ar' => 'أوربت',
        ]);
        $otherProduct = Product::factory()
            ->active()
            ->create([
                'name' => 'Other Branded Product',
                'brand_id' => $otherBrand->id,
                'sku' => 'ORB-0002',
            ]);

        $this
            ->get(route('products.index', ['q' => 'Zenith']))
            ->assertOk()
            ->assertSee($brandProduct->name)
            ->assertDontSee($otherProduct->name);

        $this
            ->get(route('products.index', ['q' => 'زينيث']))
            ->assertOk()
            ->assertSee($brandProduct->name)
            ->assertDontSee($otherProduct->name);
    }

    public function test_empty_query_browses_the_catalog_with_a_hint(): void
    {
        $product = Product::factory()
            ->active()
            ->create(['name' => 'Browsable Product']);

        $this
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee(__('catalog.search.hint'))
            ->assertSee($product->name);
    }

    public function test_short_or_invalid_query_is_treated_as_no_query(): void
    {
        $product = Product::factory()
            ->active()
            ->create(['name' => 'Short Query Product']);

        foreach (['a', ' b ', ['x']] as $query) {
            $this
                ->get(route('products.index', ['q' => $query]))
                ->assertOk()
                ->assertSee(__('catalog.search.hint'))
                ->assertSee($product->name);
        }
    }

    public function test_search_matches_the_type_of_a_product(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $type = Type::factory()->create([
            'category_id' => $category->id,
            'name' => 'Ultrawide Monitor',
        ]);

        $matching = Product::factory()
            ->active()
            ->create([
                'name' => 'Alpha Display',
                'type_id' => $type->id,
            ]);

        $otherType = Type::factory()->create([
            'category_id' => Category::factory()->active()->create(['parent_id' => null])->id,
        ]);
        $other = Product::factory()
            ->active()
            ->create([
                'name' => 'Beta Keyboard',
                'type_id' => $otherType->id,
            ]);

        // Typing what a product is finds what is of that kind.
        $this
            ->get(route('products.index', ['q' => 'ultrawide']))
            ->assertOk()
            ->assertSee($matching->name)
            ->assertDontSee($other->name);
    }

    public function test_search_matches_a_category_and_everything_under_it(): void
    {
        $category = Category::factory()
            ->active()
            ->create([
                'parent_id' => null,
                'name_en' => 'Gaming Gear',
            ]);
        $child = Category::factory()
            ->active()
            ->create([
                'parent_id' => $category->id,
                'name_en' => 'Office Chairs',
            ]);

        $onTheShelf = Product::factory()
            ->active()
            ->create([
                'name' => 'Alpha Chair',
                'type_id' => Type::factory()
                    ->create(['category_id' => $category->id])
                    ->id,
            ]);
        $underIt = Product::factory()
            ->active()
            ->create([
                'name' => 'Beta Chair',
                'type_id' => Type::factory()
                    ->create(['category_id' => $child->id])
                    ->id,
            ]);
        $unrelatedType = Type::factory()->create([
            'category_id' => Category::factory()->active()->create(['parent_id' => null])->id,
        ]);
        $other = Product::factory()
            ->active()
            ->create([
                'name' => 'Gamma Mouse',
                'type_id' => $unrelatedType->id,
            ]);

        // Naming a shelf finds what is on it and everything on the shelves under
        // it, not only what hangs on it directly.
        $this
            ->get(route('products.index', ['q' => 'Gaming Gear']))
            ->assertOk()
            ->assertSee($onTheShelf->name)
            ->assertSee($underIt->name)
            ->assertDontSee($other->name);
    }

    public function test_search_matches_a_category_name_in_arabic(): void
    {
        $category = Category::factory()
            ->active()
            ->create([
                'parent_id' => null,
                'name_en' => 'Displays',
                'name_ar' => 'شاشات',
            ]);

        $matching = Product::factory()
            ->active()
            ->create([
                'name' => 'Arabic Shelf Product',
                'type_id' => Type::factory()
                    ->create(['category_id' => $category->id])
                    ->id,
            ]);
        $otherType = Type::factory()->create([
            'category_id' => Category::factory()->active()->create(['parent_id' => null])->id,
        ]);
        $other = Product::factory()
            ->active()
            ->create([
                'name' => 'Unrelated Product',
                'type_id' => $otherType->id,
            ]);

        $this
            ->withSession(['locale' => 'ar'])
            ->get(route('products.index', ['q' => 'شاشات']))
            ->assertOk()
            ->assertSee($matching->name)
            ->assertDontSee($other->name);
    }

    public function test_search_shows_the_result_count_and_preserves_the_query(): void
    {
        Product::factory()
            ->active()
            ->create(['name' => 'Countable Keyboard']);

        $this
            ->get(route('products.index', ['q' => 'Countable']))
            ->assertOk()
            ->assertSee(
                trans_choice('catalog.search.results', 1, [
                    'query' => 'Countable',
                ]),
            )
            ->assertSee('value="Countable"', false);
    }

    public function test_search_with_no_results_shows_the_empty_state(): void
    {
        Product::factory()
            ->active()
            ->create(['name' => 'Existing Product']);

        $this
            ->get(route('products.index', ['q' => 'nonexistent']))
            ->assertOk()
            ->assertSee(
                __('catalog.search.empty_title', ['query' => 'nonexistent']),
            )
            ->assertSee(__('catalog.search.empty_message'))
            ->assertSee(__('catalog.search.clear'));
    }

    public function test_search_combines_with_brand_filter_and_sorting(): void
    {
        $brand = Brand::factory()->create(['name_en' => 'Acme']);
        $otherBrand = Brand::factory()->create(['name_en' => 'Globex']);

        $cheap = Product::factory()
            ->active()
            ->create([
                'name' => 'Omega Mechanical Keyboard',
                'brand_id' => $brand->id,
                'price_in_fils' => 1_000,
            ]);
        $expensive = Product::factory()
            ->active()
            ->create([
                'name' => 'Alpha Mechanical Keyboard',
                'brand_id' => $brand->id,
                'price_in_fils' => 9_000,
            ]);
        $otherBrandProduct = Product::factory()
            ->active()
            ->create([
                'name' => 'Delta Mechanical Keyboard',
                'brand_id' => $otherBrand->id,
                'price_in_fils' => 500,
            ]);
        $unrelated = Product::factory()
            ->active()
            ->create(['name' => 'Gamma Monitor']);

        $this
            ->get(
                route('products.index', [
                    'q' => 'Mechanical Keyboard',
                    'brand' => [$brand->id],
                    'sort' => 'price_asc',
                ]),
            )
            ->assertOk()
            ->assertSeeInOrder([$cheap->name, $expensive->name])
            ->assertDontSee($otherBrandProduct->name)
            ->assertDontSee($unrelated->name);
    }

    public function test_search_can_be_filtered_by_category(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $type = Type::factory()->create(['category_id' => $category->id]);

        $matching = Product::factory()
            ->active()
            ->create([
                'name' => 'Alpha Categorized Keyboard',
                'type_id' => $type->id,
            ]);

        $otherCategory = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $otherType = Type::factory()->create([
            'category_id' => $otherCategory->id,
        ]);
        $other = Product::factory()
            ->active()
            ->create([
                'name' => 'Beta Categorized Mouse',
                'type_id' => $otherType->id,
            ]);

        $this
            ->get(
                route('products.index', [
                    'q' => 'Categorized',
                    'category' => [$category->id],
                ]),
            )
            ->assertOk()
            ->assertSee($matching->name)
            ->assertDontSee($other->name);
    }

    public function test_search_pagination_links_preserve_query_and_filters(): void
    {
        $brand = Brand::factory()->create();

        Product::factory()
            ->active()
            ->count(13)
            ->create([
                'name' => 'Paginated Keyboard',
                'brand_id' => $brand->id,
            ]);

        $this
            ->get(
                route('products.index', [
                    'q' => 'Paginated',
                    'brand' => [$brand->id],
                ]),
            )
            ->assertOk()
            ->assertSee('q=Paginated', false)
            ->assertSee('brand%5B0%5D='.$brand->id, false)
            ->assertSee('page=2', false);
    }

    public function test_active_filter_chips_summarise_applied_filters(): void
    {
        Product::factory()
            ->active()
            ->create([
                'name' => 'Chipped Product',
                'price_in_fils' => 2_000,
                'stock' => 5,
                'reserved_stock' => 0,
            ]);

        $currency = __('homepage.currency');
        $priceLabel =
            __('catalog.filters.price')
            .': '
            .Number::format(1, maxPrecision: 3)
            .' '
            .$currency
            .' – '
            .Number::format(3, maxPrecision: 3)
            .' '
            .$currency;

        $this
            ->get(
                route('products.index', [
                    'q' => 'Chipped',
                    'price_min' => 1,
                    'price_max' => 3,
                    'availability' => 'in_stock',
                    'discounted' => 1,
                ]),
            )
            ->assertOk()
            ->assertSee(__('catalog.search.active_filters'))
            ->assertSee($priceLabel);
    }
}
