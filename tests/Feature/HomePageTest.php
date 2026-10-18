<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_homepage_renders_only_active_categories_and_products(): void
    {
        $category = Category::factory()->active()->create();
        $inactiveCategory = Category::factory()->create([
            'activated_at' => now()->addDay(),
        ]);

        $product = Product::factory()->active()->featured()->create();
        $inactiveProduct = Product::factory()
            ->featured()
            ->create(['activated_at' => now()->addDay()]);

        $banner = Banner::factory()->create();

        $this
            ->get(route('home'))
            ->assertOk()
            ->assertSee($category->name)
            ->assertDontSee($inactiveCategory->name)
            ->assertSee($product->name)
            // Nothing withdrawn from the storefront is offered, in any of the
            // sections the page gathers.
            ->assertDontSee($inactiveProduct->name)
            ->assertSee($banner->title)
            ->assertSee(__('homepage.categories.title'))
            ->assertSee(__('homepage.top_picks.title'));
    }

    public function test_the_homepage_opens_the_outline_with_a_single_h1(): void
    {
        // The homepage leads with the hero, which is a picture rather than a
        // heading, so the shop names itself in one heading kept for assistive
        // technology — the document's outline still opens with an `h1`.
        $html = $this->get(route('home'))->assertOk()->getContent();

        static::assertSame(1, substr_count($html, '<h1'));
        static::assertStringContainsString(
            '<h1 class="visually-hidden">',
            $html,
        );
    }

    public function test_a_listing_goes_from_its_page_heading_to_the_cards(): void
    {
        Product::factory()->active()->create();

        // The page names itself with an `h1`, and each card is the `h2` directly
        // under it, so the outline never skips a level.
        $this
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('<h1 class="page-header__title">', false)
            ->assertSee('<h2 class="product-card__title">', false);
    }

    public function test_the_homepage_offers_a_rail_per_way_into_the_catalogue(): void
    {
        $category = Category::factory()->active()->create(['parent_id' => null]);
        $type = Type::factory()->create(['category_id' => $category->id]);

        $featured = Product::factory()->active()->featured()->for($type)->create();
        $onSale = Product::factory()->active()->discounted()->for($type)->create();
        $newArrival = Product::factory()
            ->active()
            ->for($type)
            ->create(['featured_at' => null]);

        $this
            ->get(route('home'))
            ->assertOk()
            // Each way in has a title of its own over the cards it gathers…
            ->assertSee(__('homepage.top_picks.title'))
            ->assertSee(__('homepage.mega_deals.title'))
            ->assertSee(__('homepage.just_landed.title'))
            ->assertSee($featured->name)
            ->assertSee($onSale->name)
            ->assertSee($newArrival->name)
            // …and a way on to the catalogue narrowed the way that rail is.
            ->assertSee(
                'href="'.route('products.index', ['discounted' => 1]).'"',
                false,
            )
            ->assertSee(
                'href="'.route('products.index', ['sort' => 'newest']).'"',
                false,
            );
    }

    public function test_a_homepage_section_is_a_rail_that_scrolls_sideways(): void
    {
        Product::factory()->active()->featured()->create();

        $html = $this->get(route('home'))->assertOk()->getContent();

        static::assertStringContainsString(
            'class="categories-grid categories-grid--rail"',
            $html,
        );
        static::assertStringContainsString(
            'class="products-grid products-grid--rail"',
            $html,
        );

        // The rail is a row that scrolls rather than a grid that wraps, and it
        // keeps the scrollbar: on a desktop that is the cue a wheel has.
        $css = file_get_contents(
            resource_path('css/components/category-card.css'),
        );

        static::assertMatchesRegularExpression(
            '/\.categories-grid--rail,[\s\S]*?\.products-grid--rail \{[\s\S]*?grid-auto-flow: column;[\s\S]*?overflow-x: auto;/',
            $css,
        );

        // And the way on is a call to action rather than a footnote.
        $sectionCss = file_get_contents(
            resource_path('css/components/section-header.css'),
        );

        static::assertMatchesRegularExpression(
            '/\.view-more \{[\s\S]*?font-weight: var\(--fw-bold\);/',
            $sectionCss,
        );
    }

    /**
     * The scrollbar is the palette's everywhere, not one element's own: the
     * thumb is a primary token, written for both schemes and in both the
     * standard and the WebKit spelling.
     */
    public function test_the_scrollbar_is_the_palette_everywhere(): void
    {
        $tokens = file_get_contents(
            resource_path('css/tokens/semantic-colors.css'),
        );

        static::assertStringContainsString(
            '--clr-scrollbar-thumb: var(--primary-600);',
            $tokens,
        );
        static::assertStringContainsString(
            '--clr-scrollbar-thumb: var(--primary-400);',
            $tokens,
        );

        $base = file_get_contents(resource_path('css/base.css'));

        static::assertMatchesRegularExpression(
            '/scrollbar-color: var\(--clr-scrollbar-thumb\) var\(--clr-scrollbar-track\);/',
            $base,
        );
        static::assertStringContainsString('::-webkit-scrollbar-thumb', $base);

        // The scrollbar's room is kept whether or not there is a scrollbar, so
        // the page does not shift when content grows past the fold…
        static::assertStringContainsString('scrollbar-gutter: stable;', $base);

        // …and the engines that do not know the property keep that room the
        // older way rather than losing it.
        static::assertMatchesRegularExpression(
            '/@supports not \(scrollbar-gutter: stable\) \{[\s\S]*?overflow-y: scroll;/',
            $base,
        );
    }

    /**
     * A row is moved by its own pair of buttons: the markup names them, and the
     * script is what ties them to the row they sit over.
     */
    public function test_a_section_carries_its_own_way_along(): void
    {
        Product::factory()->active()->featured()->create();

        $html = $this->get(route('home'))->assertOk()->getContent();

        static::assertStringContainsString('data-scroller-nav', $html);
        static::assertStringContainsString('data-scroller-step="previous"', $html);
        static::assertStringContainsString('data-scroller-step="next"', $html);

        static::assertStringContainsString(
            'aria-label="'.__('homepage.carousel.previous').'"',
            $html,
        );
        static::assertStringContainsString(
            'aria-label="'.__('homepage.carousel.next').'"',
            $html,
        );

        // The row the pair drives is the one beside it in the same group.
        static::assertStringContainsString(
            'class="products-grid products-grid--rail" data-scroller',
            $html,
        );
    }

    /**
     * "Previous" is towards the start of the row, which a right-to-left page puts
     * on the right: the buttons follow the writing direction and the chevrons
     * turn with it, rather than the layout being mirrored by hand.
     */
    public function test_the_scroller_controls_follow_the_writing_direction(): void
    {
        $css = file_get_contents(
            resource_path('css/components/scroller-nav.css'),
        );

        static::assertMatchesRegularExpression(
            '/\[dir="rtl"\] \.scroller-nav__icon \{[\s\S]*?transform: scaleX\(-1\);/',
            $css,
        );

        // And the script reads the direction off the row rather than assuming
        // it, so that the same button is "forward" either way round.
        $script = file_get_contents(
            resource_path('js/components/scroller-nav.js'),
        );

        static::assertStringContainsString(
            'getComputedStyle(rail).direction === "rtl"',
            $script,
        );

        // The page that runs right to left is the one this has to hold for.
        Product::factory()->active()->featured()->create();

        $this
            ->withSession(['locale' => 'ar'])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee(__('homepage.carousel.previous'));
    }

    /**
     * The hero is the same scroller a section is — one banner at a time, the
     * same pair of buttons, the same direction-aware step.
     */
    public function test_the_hero_shows_one_banner_at_a_time(): void
    {
        Banner::factory()->create();

        $html = $this->get(route('home'))->assertOk()->getContent();

        static::assertStringContainsString('data-scroller-group', $html);
        static::assertStringContainsString('data-scroller', $html);
        static::assertStringContainsString('scroller-nav__button', $html);

        $css = file_get_contents(
            resource_path('css/components/banner-slider.css'),
        );

        // A slide is the whole track, and a drag settles near a banner rather
        // than being locked to one.
        static::assertMatchesRegularExpression(
            '/\.banner-slider__slide \{[\s\S]*?flex: 0 0 100%;/',
            $css,
        );
        static::assertMatchesRegularExpression(
            '/\.banner-slider__track \{[\s\S]*?scroll-snap-type: x proximity;/',
            $css,
        );
    }

    public function test_homepage_banner_slider_is_hidden_without_banners(): void
    {
        $this
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('banner-slider__slide', false);
    }

    public function test_a_banner_title_is_shown_in_the_language_the_page_is_read_in(): void
    {
        Banner::factory()->create([
            'title_en' => 'Back to School Sale',
            'title_ar' => 'عروض العودة إلى المدرسة',
        ]);

        $this
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Back to School Sale')
            ->assertDontSee('عروض العودة إلى المدرسة');

        $this
            ->withSession(['locale' => 'ar'])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('عروض العودة إلى المدرسة')
            ->assertDontSee('Back to School Sale');
    }

    public function test_products_index_filters_by_search_query(): void
    {
        $matchingProduct = Product::factory()
            ->active()
            ->create(['name' => 'Gaming Chair XYZ']);
        $otherProduct = Product::factory()
            ->active()
            ->create(['name' => 'Processor ABC']);

        $this
            ->get(route('products.index', ['q' => 'gaming']))
            ->assertOk()
            ->assertSee($matchingProduct->name)
            ->assertDontSee($otherProduct->name);
    }

    public function test_products_index_search_cannot_be_used_for_injection_or_wildcards(): void
    {
        $percentProduct = Product::factory()
            ->active()
            ->create(['name' => '100% Cotton Desk']);
        $otherProduct = Product::factory()
            ->active()
            ->create(['name' => 'Plain Plastic Desk']);

        // A SQL fragment is bound, so it is treated as a literal term and the
        // products table is untouched.
        $this
            ->get(
                route('products.index', [
                    'q' => "'; DROP TABLE products; --",
                ]),
            )
            ->assertOk()
            ->assertDontSee($percentProduct->name)
            ->assertDontSee($otherProduct->name);

        $this->assertDatabaseCount('products', 2);

        // LIKE wildcards are escaped, so a literal "%" in the term only
        // matches a literal "%".
        $this
            ->get(route('products.index', ['q' => '100%']))
            ->assertOk()
            ->assertSee($percentProduct->name)
            ->assertDontSee($otherProduct->name);

        // Over-long terms match nothing rather than hitting the database.
        $this
            ->get(route('products.index', ['q' => str_repeat('a', 200)]))
            ->assertOk()
            ->assertSee(__('catalog.search.empty_message'));
    }

    public function test_categories_index_lists_only_top_level_categories(): void
    {
        $topLevelCategory = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $childCategory = Category::factory()
            ->active()
            ->create(['parent_id' => $topLevelCategory->id]);

        $this
            ->get(route('category.index'))
            ->assertOk()
            ->assertSee($topLevelCategory->name)
            ->assertViewHas('sections', static function ($sections) use ($topLevelCategory, $childCategory) {
                $ids = collect($sections->items())->map(
                    static fn ($section) => $section->category->id,
                );

                return $ids->contains($topLevelCategory->id) && ! $ids->contains($childCategory->id);
            });
    }

    public function test_category_page_lists_only_its_products(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);

        $type = Type::factory()->create(['category_id' => $category->id]);
        $otherType = Type::factory()->create([
            'category_id' => Category::factory()
                ->active()
                ->create(['parent_id' => null])
                ->id,
        ]);

        $product = Product::factory()
            ->active()
            ->create(['type_id' => $type->id]);
        $otherProduct = Product::factory()
            ->active()
            ->create(['type_id' => $otherType->id]);

        $this
            ->get(route('category.show', $category->slug))
            ->assertOk()
            ->assertSee($category->name)
            ->assertSee($product->name)
            ->assertDontSee($otherProduct->name);
    }

    public function test_every_homepage_call_to_action_points_somewhere_real(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $type = Type::factory()->create(['category_id' => $category->id]);
        $product = Product::factory()
            ->active()
            ->featured()
            ->create(['type_id' => $type->id, 'stock' => 5]);

        Banner::factory()->create([
            'link' => route('category.show', $category->slug),
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        // Nothing on the page is a dead end: every link goes somewhere.
        preg_match_all('/href="([^"]*)"/', $html, $links);

        static::assertNotEmpty($links[1]);

        foreach ($links[1] as $href) {
            static::assertNotSame(
                '#',
                $href,
                'The homepage has a link that goes nowhere.',
            );
            static::assertNotSame(
                '',
                $href,
                'The homepage has a link with no target.',
            );
        }

        // The calls to action the page promises, each the route it belongs to:
        // the catalogue, a category, a product, the search box, add to cart and
        // the wishlist toggle.
        static::assertStringContainsString(
            'href="'.route('category.index').'"',
            $html,
        );
        static::assertStringContainsString(
            'href="'.route('category.show', $category->slug).'"',
            $html,
        );
        static::assertStringContainsString(
            'href="'.route('products.show', $product->slug).'"',
            $html,
        );
        static::assertStringContainsString(
            'action="'.route('products.index').'"',
            $html,
        );
        static::assertStringContainsString(
            'action="'.route('cart.add').'"',
            $html,
        );
        static::assertStringContainsString(
            'action="'.route('wishlist.add').'"',
            $html,
        );

        // Every form that writes carries the token it has to.
        foreach (explode('<form', $html) as $form) {
            if (! str_contains($form, 'method="POST"')) {
                continue;
            }

            static::assertStringContainsString('name="_token"', $form);
        }
    }

    public function test_homepage_translation_files_are_in_sync(): void
    {
        foreach (['homepage', 'components'] as $file) {
            $englishKeys = Arr::dot(include lang_path("en/{$file}.php"));
            $arabicKeys = Arr::dot(include lang_path("ar/{$file}.php"));

            static::assertEqualsCanonicalizing(
                array_keys($englishKeys),
                array_keys($arabicKeys),
                "Translation keys for \"{$file}\" are out of sync between English and Arabic.",
            );
        }
    }
}
