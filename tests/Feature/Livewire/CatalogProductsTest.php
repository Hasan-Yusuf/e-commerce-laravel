<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Type;
use App\Services\Catalog\LoadedProducts;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The products a catalogue listing narrows to — the toolbar over them and the
 * grid or list itself — as a component of their own. A search, a sort or a view
 * is answered here alone, and the filters beside it announce what to narrow to,
 * so the products follow a tick without the sidebar being rendered for it.
 */
class CatalogProductsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_products_pick_up_the_filters_that_are_announced(): void
    {
        [$category, $type] = $this->catalog();

        $zenith = Brand::factory()->create();
        $other = Brand::factory()->create();

        $this->product($type, 'Wanted Product', ['brand_id' => $zenith->id]);
        $this->product($type, 'Other Product', ['brand_id' => $other->id]);

        Livewire::test('catalog-products', ['categoryId' => $category->id])
            ->assertSee('Wanted Product')
            ->assertSee('Other Product')
            ->set('page', 2)
            ->assertSet('page', 2)
            // The announcement is the whole of what the filters say.
            ->dispatch('filters-updated', filters: ['brand' => [(string) $zenith->id]])
            ->assertSee('Wanted Product')
            ->assertDontSee('Other Product')
            // Page 2 of the old listing is not page 2 of this one.
            ->assertSet('page', 1);
    }

    public function test_a_search_is_answered_by_the_products_alone(): void
    {
        [$category, $type] = $this->catalog();

        $this->product($type, 'Black Product', ['Memory' => 16, 'Colour' => 'Black']);
        $this->product($type, 'Silver Product', ['Memory' => 16, 'Colour' => 'Silver']);

        Livewire::test('catalog-products', ['categoryId' => $category->id])
            ->set('page', 2)
            ->set('q', 'silver')
            ->assertSee('Silver Product')
            ->assertDontSee('Black Product')
            ->assertSet('page', 1)
            // Nothing of the sidebar is rendered for it: the filters answer
            // their own side of the listing.
            ->assertDontSee('filter-sidebar', false);
    }

    public function test_a_sort_and_a_view_are_answered_in_place(): void
    {
        [$category, $type] = $this->catalog();

        $this->product($type, 'Black Product', ['Memory' => 16, 'Colour' => 'Black']);

        Livewire::test('catalog-products', ['categoryId' => $category->id])
            ->assertSee('products-grid', false)
            ->set('view', 'list')
            ->assertSee('product-list', false)
            ->assertSee('product-row', false)
            ->assertDontSee('products-grid', false)
            ->set('sort', 'price_asc')
            ->assertSet('sort', 'price_asc');
    }

    public function test_the_toolbar_forms_post_to_the_listing_not_the_update_endpoint(): void
    {
        [$category, $type] = $this->catalog();

        $this->product($type, 'Black Product', []);

        // The search, the sort and the view are forms so they post without
        // JavaScript. Their action is the listing's own URL — never
        // `url()->current()`, which on a Livewire re-render is the update
        // endpoint a browser's implicit submit (Enter on the sort select)
        // cannot GET.
        Livewire::test('catalog-products')
            ->assertSee('action="'.route('products.index').'"', false);

        Livewire::test('catalog-products', ['categoryId' => $category->id])
            ->assertSee('action="'.route('category.show', $category).'"', false);
    }

    public function test_the_products_are_scoped_to_the_category_they_are_told(): void
    {
        [$category, $type] = $this->catalog();

        $this->product($type, 'Inside Product', []);

        [$otherCategory, $otherType] = $this->catalog();

        $this->product($otherType, 'Outside Product', []);

        Livewire::test('catalog-products', ['categoryId' => $category->id])
            ->assertSee('Inside Product')
            ->assertDontSee('Outside Product');

        // No category is the whole shop: both are there.
        Livewire::test('catalog-products')
            ->assertSee('Inside Product')
            ->assertSee('Outside Product');
    }

    public function test_the_toolbar_answers_the_products_rather_than_a_posted_form(): void
    {
        [$category, $type] = $this->catalog();

        $this->product($type, 'Listed Product', ['Memory' => 16, 'Colour' => 'Black']);

        $this
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('wire:model.live.debounce.500ms="q"', false)
            ->assertSee('wire:model.live="sort"', false)
            ->assertSee('wire:model.live="view"', false)
            // Kept as a form the browser without JavaScript can post.
            ->assertSee('class="catalog-sort"', false)
            ->assertSee('class="catalog-view"', false)
            ->assertDontSee('data-auto-submit', false);
    }

    /**
     * A page of cards costs a constant number of discount queries, not a pair
     * per card: eleven more discounted products do not add a query.
     */
    public function test_the_listing_does_not_run_a_discount_query_per_card(): void
    {
        [$category, $type] = $this->catalog();

        $discount = Discount::factory()->percentage(10)->create();
        $discount->products()->attach($this->product($type, 'First Product', []));

        $oneProduct = $this->countDiscountQueriesFor($category);

        foreach (range(2, 12) as $position) {
            $discount->products()->attach($this->product($type, "Product {$position}", []));
        }

        $twelveProducts = $this->countDiscountQueriesFor($category);

        static::assertSame(
            $oneProduct['discountables'],
            $twelveProducts['discountables'],
            'The discountables are read once per card.',
        );

        static::assertSame(
            $oneProduct['discounts'],
            $twelveProducts['discounts'],
            'The discounts are read once per card.',
        );

        static::assertSame(1, $twelveProducts['discountables']);
        static::assertSame(1, $twelveProducts['discounts']);
    }

    /**
     * The listing grows downwards: reading the next batch appends it, and the
     * batch already there is kept.
     */
    public function test_reading_the_next_batch_appends_it_without_repeating_the_first(): void
    {
        [$category, $type] = $this->catalog();

        foreach (range(1, 20) as $position) {
            $this->product($type, sprintf('Item %02d', $position), []);
        }

        $component = Livewire::test('catalog-products', [
            'categoryId' => $category->id,
        ]);

        $component->assertSet('page', 1);
        static::assertSame(12, $this->cardCount($component->html()));

        $component->call('loadMore')->assertSet('page', 2);

        $html = $component->html();

        // The whole listing, each product once: the first batch was kept and
        // the next one appended, and nothing was read twice.
        static::assertSame(20, $this->cardCount($html));
        static::assertSame(1, substr_count($html, 'Item 01'));
        static::assertSame(1, substr_count($html, 'Item 20'));

        $component->assertSee(__('catalog.pagination.end'));
    }

    /**
     * The way on is a link, so the next batch is still reachable in a browser
     * without JavaScript: the page comes back holding every batch it names.
     */
    public function test_the_next_batch_is_a_link_a_browser_without_javascript_can_follow(): void
    {
        [$category, $type] = $this->catalog();

        foreach (range(1, 25) as $position) {
            $this->product($type, sprintf('Item %02d', $position), []);
        }

        // The second batch: the first 24 products, and a link to the third.
        $html = $this
            ->get(route('products.index', ['page' => 2]))
            ->assertOk()
            ->assertSee(__('catalog.pagination.load_more'))
            ->assertSee('page=3', false)
            ->getContent();

        static::assertSame(24, $this->cardCount($html));
    }

    /**
     * The end of the listing is said rather than left to a control that would
     * ask for a batch that is not coming.
     */
    public function test_the_end_of_the_listing_is_announced_instead_of_offering_more(): void
    {
        [$category, $type] = $this->catalog();

        // Fewer than one batch: there is nothing after the first render.
        foreach (range(1, 5) as $position) {
            $this->product($type, sprintf('Item %02d', $position), []);
        }

        Livewire::test('catalog-products', ['categoryId' => $category->id])
            ->assertSee(__('catalog.pagination.end'))
            ->assertDontSee(__('catalog.pagination.load_more'));
    }

    /**
     * A card does not read back the product the listing already read: the set
     * is handed over, so the pages of cards a scroll asks for cost no query
     * per card.
     */
    public function test_the_cards_do_not_read_their_product_again(): void
    {
        [$category, $type] = $this->catalog();

        $this->product($type, 'First Product', []);
        $this->product($type, 'Second Product', []);

        $withTwoProducts = $this->countProductQueriesFor($category);

        foreach (range(3, 12) as $position) {
            $this->product($type, "Product {$position}", []);
        }

        $withTwelveProducts = $this->countProductQueriesFor($category);

        static::assertSame(
            $withTwoProducts,
            $withTwelveProducts,
            'The listing reads the products back once per card.',
        );

        // The listing itself and its count; nothing per card.
        static::assertLessThanOrEqual(3, $withTwelveProducts);
    }

    /**
     * A page that is not a batch — zero, or below — is the first batch, not the
     * whole catalogue: how many batches have been read is a count to multiply,
     * not a limit to hand straight to the database.
     */
    public function test_a_nonsense_page_reads_the_first_batch_rather_than_everything(): void
    {
        [$category, $type] = $this->catalog();

        foreach (range(1, 20) as $position) {
            $this->product($type, sprintf('Item %02d', $position), []);
        }

        foreach ([0, -5] as $page) {
            $html = $this
                ->get(route('products.index', ['page' => $page]))
                ->assertOk()
                ->getContent();

            static::assertSame(
                12,
                $this->cardCount($html),
                "Page {$page} did not read the first batch.",
            );
        }
    }

    /**
     * The scroll is wired to the control as it comes into range — before the
     * shopper reaches the bottom — and the wait is a live region, so it is
     * announced rather than only drawn.
     */
    public function test_the_next_batch_is_wired_to_the_scroll_and_announced(): void
    {
        [$category, $type] = $this->catalog();

        foreach (range(1, 13) as $position) {
            $this->product($type, sprintf('Item %02d', $position), []);
        }

        $html = Livewire::test('catalog-products', [
            'categoryId' => $category->id,
        ])->html();

        static::assertStringContainsString(
            'x-intersect.margin.600px="$wire.loadMore()"',
            $html,
        );
        static::assertStringContainsString(
            'wire:click.prevent="loadMore"',
            $html,
        );
        static::assertStringContainsString('wire:loading.delay', $html);
        static::assertStringContainsString('wire:loading.attr="aria-busy"', $html);
        static::assertStringContainsString('role="status"', $html);
        static::assertStringContainsString('aria-live="polite"', $html);
    }

    /**
     * The pictures the listing opens on are fetched with the document and asked
     * for first, because those are the cards a visitor is waiting for. The ones
     * below them are only fetched as they are scrolled to, and every picture is
     * decoded off the main thread, so a listing that keeps growing neither waits
     * on pictures nobody has reached nor blocks on the ones it has.
     */
    public function test_card_images_are_prioritised_above_the_fold_and_lazy_below_it(): void
    {
        Storage::fake('public');

        [$category, $type] = $this->catalog();

        foreach (range(1, 6) as $position) {
            $this
                ->product($type, "Illustrated Product {$position}", [])
                ->addMedia(UploadedFile::fake()->image("thumb-{$position}.png", 640, 480))
                ->toMediaCollection('thumbnail');
        }

        $html = Livewire::test('catalog-products', [
            'categoryId' => $category->id,
        ])->html();

        // The listing opens on four cards — the component's own notion of what
        // is on screen — and those are the ones asked for first.
        static::assertSame(4, substr_count($html, 'fetchpriority="high"'));
        static::assertSame(2, substr_count($html, 'loading="lazy"'));

        // Whichever end of the listing a card is on, its picture is decoded
        // away from the main thread.
        static::assertSame(6, substr_count($html, 'decoding="async"'));

        // Each card is told the width it is drawn at, and offers the browser
        // the widths the collection keeps, so a card asks for a thumbnail-sized
        // file rather than the original the shop uploaded.
        static::assertSame(6, substr_count($html, 'sizes="(min-width: 600px) 300px, calc(100vw - 32px)"'));
        static::assertSame(6, substr_count($html, 'srcset='));
    }

    private function cardCount(string $html): int
    {
        return substr_count($html, 'product-card__link');
    }

    private function countProductQueriesFor(Category $category): int
    {
        // A fresh request: the products a previous render handed over are not
        // carried into this one in the application either.
        app()->forgetInstance(LoadedProducts::class);

        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::test('catalog-products', ['categoryId' => $category->id]);

        $queries = collect(DB::getQueryLog())->pluck('query');

        DB::disableQueryLog();

        return $queries
            ->filter(
                static fn (string $query): bool => str_contains($query, 'from "products"'),
            )
            ->count();
    }

    /**
     * @return array{discountables: int, discounts: int}
     */
    private function countDiscountQueriesFor(Category $category): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::test('catalog-products', ['categoryId' => $category->id]);

        $queries = collect(DB::getQueryLog())->pluck('query');

        DB::disableQueryLog();

        return [
            'discountables' => $queries
                ->filter(static fn (string $query): bool => str_contains($query, 'discountables'))
                ->count(),
            'discounts' => $queries
                ->filter(static fn (string $query): bool => str_contains($query, 'from "discounts"'))
                ->count(),
        ];
    }

    /**
     * A category holding one type with the factory's spec schema.
     *
     * @return array{0: Category, 1: Type}
     */
    private function catalog(?Category $category = null): array
    {
        $category ??= Category::factory()->active()->create(['parent_id' => null]);

        return [$category, Type::factory()->create(['category_id' => $category->id])];
    }

    /**
     * A product of the type carrying the given English spec values.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function product(Type $type, string $name, array $attributes): Product
    {
        $specs = collect($attributes)->filter(static fn (mixed $value, string $key): bool => $type->specDefinitions
            ->contains('name_en', $key))
            ->all();

        return Product::factory()
            ->active()
            ->for($type)
            ->withSpecs($specs)
            ->create(array_merge(
                [
                    'name' => $name,
                    'stock' => 5,
                    'reserved_stock' => 0,
                    'low_stock_threshold' => 0,
                ],
                array_diff_key($attributes, $specs),
            ));
    }
}
