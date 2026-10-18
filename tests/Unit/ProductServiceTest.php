<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use App\Services\Catalog\ProductService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * The homepage's product rails, each a question about the catalogue under its
 * own title. The queries live in the service rather than the controller, so they
 * can be answered — and asserted — without a page being rendered.
 */
class ProductServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private ProductService $productService;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->productService = app(ProductService::class);
    }

    public function test_the_sections_are_the_three_rails_the_view_reads(): void
    {
        // The keys are the view's own variable names: the controller spreads
        // them, so the view reads each rail by the name it goes under.
        static::assertSame(
            ['topPicks', 'megaDeals', 'justLanded'],
            array_keys($this->productService->getHomePageSections()),
        );
    }

    public function test_each_rail_gathers_the_products_its_title_promises(): void
    {
        $type = $this->type();

        // The featured product carries a past `featured_at`; the discounted one
        // deliberately does not, and the newest one is neither featured nor
        // discounted.
        $featured = Product::factory()
            ->active()
            ->featured()
            ->for($type)
            ->create();
        $onSale = Product::factory()
            ->active()
            ->discounted()
            ->for($type)
            ->create(['featured_at' => null]);
        $newArrival = Product::factory()
            ->active()
            ->for($type)
            ->create(['featured_at' => null]);

        $sections = $this->productService->getHomePageSections();

        static::assertSame(
            [$featured->id],
            $sections['topPicks']->pluck('product.id')->all(),
            'The Top Picks rail is not the featured products.',
        );

        static::assertSame(
            [$onSale->id],
            $sections['megaDeals']->pluck('product.id')->all(),
            'The Mega Deals rail is not the discounted products.',
        );

        static::assertEqualsCanonicalizing(
            [$featured->id, $onSale->id, $newArrival->id],
            $sections['justLanded']->pluck('product.id')->all(),
            'The Just Landed rail is not the newest active products.',
        );
    }

    public function test_no_rail_offers_a_product_that_is_not_active(): void
    {
        $type = $this->type();

        // Featured and discounted, but not on sale yet: nothing may offer it.
        $withdrawn = Product::factory()
            ->featured()
            ->discounted()
            ->for($type)
            ->create(['activated_at' => now()->addDay()]);

        foreach ($this->productService->getHomePageSections() as $name => $rail) {
            static::assertNotContains(
                $withdrawn->id,
                $rail->pluck('product.id')->all(),
                "The {$name} rail offers a product that is not active.",
            );
        }
    }

    public function test_a_rail_holds_no_more_than_it_is_asked_for(): void
    {
        $type = $this->type();

        foreach (range(1, 3) as $position) {
            Product::factory()
                ->active()
                ->featured()
                ->for($type)
                ->create(['name' => "Featured {$position}"]);
        }

        static::assertCount(3, $this->productService->getFeaturedProducts());
        static::assertCount(2, $this->productService->getFeaturedProducts(2));

        // The sections array takes one count for every rail at once.
        foreach ($this->productService->getHomePageSections(2) as $rail) {
            static::assertLessThanOrEqual(2, $rail->count());
        }
    }

    private function type(): Type
    {
        return Type::factory()->create([
            'category_id' => Category::factory()
                ->active()
                ->create(['parent_id' => null])
                ->id,
        ]);
    }
}
