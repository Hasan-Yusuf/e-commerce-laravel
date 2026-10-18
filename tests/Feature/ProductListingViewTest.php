<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductListingViewTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_listing_defaults_to_the_grid_view_with_a_view_toggle(): void
    {
        $this->createProduct();

        $this
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('catalog-view', false)
            ->assertSee('products-grid', false)
            ->assertDontSee('product-list', false);
    }

    public function test_the_list_view_renders_cart_style_rows(): void
    {
        $this->createProduct();

        $this
            ->get(route('products.index', ['view' => 'list']))
            ->assertOk()
            ->assertSee('product-list', false)
            ->assertSee('product-row', false)
            ->assertDontSee('products-grid', false)
            // The choice is carried into the sort/filter forms.
            ->assertSee('name="view" value="list"', false);
    }

    public function test_an_unknown_view_falls_back_to_the_grid(): void
    {
        $this->createProduct();

        $this
            ->get(route('products.index', ['view' => 'cards']))
            ->assertOk()
            ->assertSee('products-grid', false)
            ->assertDontSee('product-list', false);
    }

    public function test_the_filter_sidebar_sticks_below_the_navigation(): void
    {
        $this->createProduct();

        $this
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('class="filter-panel"', false)
            ->assertSee('class="filter-sidebar"', false);

        $css = file_get_contents(resource_path('css/features/filters.css'));

        // The panel is the sticky part (the sidebar is only as tall as it is),
        // pinned and capped to the viewport so the longer lists stay reachable.
        static::assertMatchesRegularExpression(
            '/@media \(min-width: 900px\) \{\s*\.filter-panel \{[\s\S]*?position: sticky;/',
            $css,
        );
        static::assertStringContainsString('100dvh', $css);
        static::assertStringContainsString('overflow-y: auto', $css);

        // From the navigation breakpoint it pins below the sticky nav with a
        // gap, so the panel never touches the navigation.
        static::assertMatchesRegularExpression(
            '/@media \(min-width: 1161px\) \{\s*\.filter-panel \{\s*--_sticky-offset: calc\(\s*var\(--size-sticky-nav\) \+ var\(--space-400\)\s*\);/',
            $css,
        );

        // Below the breakpoint it is still the off-canvas drawer.
        static::assertMatchesRegularExpression(
            '/\.filter-sidebar \{[\s\S]*?position: fixed;/',
            $css,
        );
    }

    public function test_the_filter_header_sticks_to_the_top_of_the_sidebar(): void
    {
        $css = file_get_contents(resource_path('css/features/filters.css'));

        // The title and the line under it ride at the top of the scrolling
        // sidebar, on a panel that is opaque so the filters read behind it not
        // through it.
        static::assertMatchesRegularExpression(
            '/\.filter-sidebar__header \{[\s\S]*?position: sticky;[\s\S]*?inset-block-start: 0;/',
            $css,
        );
        static::assertMatchesRegularExpression(
            '/\.filter-sidebar__header \{[\s\S]*?background-color: var\(--clr-surface-card\);/',
            $css,
        );
        static::assertMatchesRegularExpression(
            '/\.filter-sidebar__header \{[\s\S]*?border-block-end: var\(--border-width-xs\) solid var\(--clr-border-subtle\);/',
            $css,
        );
    }

    public function test_a_filter_is_ruled_off_from_the_one_before_it(): void
    {
        $css = file_get_contents(resource_path('css/features/filters.css'));

        // The line between filters is the shared separator, kept to the weight
        // the rest of the card's lines carry rather than its heavier default…
        static::assertMatchesRegularExpression(
            '/\.separator\.filter-separator \{[^}]*--_thickness: var\(--border-width-xs\);[^}]*--_color: var\(--clr-border-subtle\);[^}]*\}/s',
            $css,
        );

        // …with its own spaces left alone: they are what every gap in the
        // sidebar is made of, so all of them agree.
        static::assertStringNotContainsString('--_space', $css);

        // The box is gone: a filter draws no border or padding of its own.
        preg_match('/\n    \.filter-group \{(?<body>.*?)\n    \}/s', $css, $group);

        static::assertArrayHasKey('body', $group);
        static::assertStringNotContainsString('border', $group['body']);
        static::assertStringNotContainsString('padding', $group['body']);
        static::assertStringNotContainsString('.filter-group__legend', $css);
    }

    public function test_every_filter_is_followed_by_a_separator(): void
    {
        $this->createProduct();

        $html = $this->get(route('products.index'))->assertOk()->getContent();

        // One rule per filter: every group, and the two lone questions, which
        // are a filter of their own.
        static::assertSame(
            substr_count($html, 'role="group"') + 1,
            substr_count(
                $html,
                'class="separator separator--horizontal filter-separator"',
            ),
        );
    }

    public function test_a_filter_is_a_named_group_without_a_fieldset(): void
    {
        $this->createProduct();

        $html = $this->get(route('products.index'))->assertOk()->getContent();

        // The controls of a filter are still grouped and still named for what
        // they filter by; the element that drew the box is gone.
        static::assertStringNotContainsString('<fieldset class="filter-group"', $html);
        static::assertStringContainsString('class="filter-group"', $html);
        static::assertStringContainsString('role="group"', $html);
        static::assertStringContainsString('aria-label="Brand"', $html);
        static::assertStringContainsString('class="filter-group__title"', $html);
    }

    public function test_product_cards_name_the_brand_above_the_product_name(): void
    {
        [$brand] = $this->createProductWithBrand();

        $this
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('product-card__brand">'.$brand->name, false)
            ->assertDontSee('brand-logo', false);
    }

    public function test_product_cards_name_the_brand_even_when_it_has_a_logo(): void
    {
        Storage::fake('public');

        [$brand] = $this->createProductWithBrand();

        $brand->addMedia(UploadedFile::fake()->image('light.png', 240, 80))->toMediaCollection('logo_light');

        // The card names the brand rather than drawing its logo, so every card
        // reads the same whatever the shop uploaded.
        $this
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('product-card__brand">'.$brand->name, false)
            ->assertDontSee('brand-logo', false);
    }

    private function createProduct(): Product
    {
        return Product::factory()
            ->active()
            ->create([
                'stock' => 5,
                'reserved_stock' => 0,
                'low_stock_threshold' => 0,
            ]);
    }

    /**
     * An active product whose card carries its own brand.
     *
     * @return array{0: Brand, 1: Product}
     */
    private function createProductWithBrand(): array
    {
        $brand = Brand::factory()->create(['name_en' => 'RigWorks']);

        $product = Product::factory()
            ->active()
            ->create([
                'brand_id' => $brand->id,
                'stock' => 5,
                'reserved_stock' => 0,
                'low_stock_threshold' => 0,
            ]);

        return [$brand, $product];
    }
}
