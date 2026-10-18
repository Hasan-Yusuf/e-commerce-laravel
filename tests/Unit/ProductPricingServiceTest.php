<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Discount;
use App\Models\Product;
use App\Services\Catalog\ProductCardAssembler;
use App\Services\Catalog\ProductPricingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductPricingServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private ProductPricingService $pricingService;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->pricingService = app(ProductPricingService::class);
    }

    public function test_returns_raw_price_when_no_discount_applies(): void
    {
        $product = $this->createProduct();

        static::assertSame(
            20_000,
            $this->pricingService->getCurrentPrice($product),
        );
        static::assertSame(
            0,
            $this->pricingService->getDiscountAmount($product),
        );
        static::assertNull($this->pricingService->getDiscount($product));
        static::assertTrue(
            $this->pricingService->getDiscounts($product)->isEmpty(),
        );
    }

    public function test_applies_product_level_percentage_discount(): void
    {
        $product = $this->createProduct();

        $discount = Discount::factory()->percentage(10)->create();
        $discount->products()->attach($product);

        static::assertSame(
            18_000,
            $this->pricingService->getCurrentPrice($product),
        );
        static::assertSame(
            2_000,
            $this->pricingService->getDiscountAmount($product),
        );
        static::assertSame(
            $discount->id,
            $this->pricingService->getDiscount($product)?->id,
        );
    }

    public function test_applies_product_level_fixed_amount_discount(): void
    {
        $product = $this->createProduct();

        $discount = Discount::factory()->fixedAmount(1.5)->create();
        $discount->products()->attach($product);

        static::assertSame(
            18_500,
            $this->pricingService->getCurrentPrice($product),
        );
        static::assertSame(
            1_500,
            $this->pricingService->getDiscountAmount($product),
        );
        static::assertSame(
            $discount->id,
            $this->pricingService->getDiscount($product)?->id,
        );
    }

    public function test_ignores_inactive_and_expired_discounts(): void
    {
        $product = $this->createProduct();

        $expired = Discount::factory()->percentage(30)->expired()->create();
        $expired->products()->attach($product);

        $upcoming = Discount::factory()->percentage(30)->upcoming()->create();
        $upcoming->products()->attach($product);

        $inactive = Discount::factory()->percentage(30)->inactive()->create();
        $inactive->products()->attach($product);

        static::assertSame(
            20_000,
            $this->pricingService->getCurrentPrice($product),
        );
        static::assertNull($this->pricingService->getDiscount($product));
    }

    public function test_applies_open_ended_discount_without_dates(): void
    {
        $product = $this->createProduct();

        $discount = Discount::factory()->percentage(10)->openEnded()->create();
        $discount->products()->attach($product);

        static::assertTrue($discount->isCurrentlyActive());
        static::assertSame(
            18_000,
            $this->pricingService->getCurrentPrice($product),
        );
    }

    public function test_applies_type_level_discount(): void
    {
        $product = $this->createProduct();

        $discount = Discount::factory()->percentage(25)->create();
        $discount->productTypes()->attach($product->type);

        static::assertSame(
            15_000,
            $this->pricingService->getCurrentPrice($product),
        );
    }

    public function test_applies_category_level_discount(): void
    {
        $product = $this->createProduct();

        $discount = Discount::factory()->percentage(25)->create();
        $discount->categories()->attach($product->category);

        static::assertSame(
            15_000,
            $this->pricingService->getCurrentPrice($product),
        );
    }

    public function test_applies_brand_level_discount(): void
    {
        $product = $this->createProduct();

        $discount = Discount::factory()->percentage(25)->create();
        $discount->brands()->attach($product->brand);

        static::assertSame(
            15_000,
            $this->pricingService->getCurrentPrice($product),
        );
    }

    public function test_ignores_discounts_targeting_other_products(): void
    {
        $product = $this->createProduct();
        $otherProduct = $this->createProduct();

        $discount = Discount::factory()->percentage(50)->create();
        $discount->products()->attach($otherProduct);

        static::assertSame(
            20_000,
            $this->pricingService->getCurrentPrice($product),
        );
    }

    public function test_lowest_price_wins_when_multiple_discounts_overlap(): void
    {
        $product = $this->createProduct();

        $smallerDiscount = Discount::factory()->percentage(10)->create();
        $smallerDiscount->products()->attach($product);

        $biggerDiscount = Discount::factory()->percentage(20)->create();
        $biggerDiscount->products()->attach($product);

        static::assertSame(
            16_000,
            $this->pricingService->getCurrentPrice($product),
        );
        static::assertSame(
            $biggerDiscount->id,
            $this->pricingService->getDiscount($product)?->id,
        );
    }

    public function test_discounted_price_is_never_negative(): void
    {
        $product = $this->createProduct(5_000);

        $discount = Discount::factory()->fixedAmount(10)->create();
        $discount->products()->attach($product);

        static::assertSame(0, $this->pricingService->getCurrentPrice($product));
    }

    public function test_a_saved_discount_change_re_prices_the_product(): void
    {
        $product = $this->createProduct();

        $discount = Discount::factory()->percentage(10)->create();
        $discount->products()->attach($product);

        static::assertSame(18_000, $this->pricingService->getCurrentPrice($product));

        $discount->update(['value' => 50]);

        static::assertSame(10_000, $this->pricingService->getCurrentPrice($product));
    }

    public function test_a_removed_discount_stops_being_applied(): void
    {
        $product = $this->createProduct();

        $discount = Discount::factory()->percentage(10)->create();
        $discount->products()->attach($product);

        static::assertSame(18_000, $this->pricingService->getCurrentPrice($product));

        $discount->delete();

        static::assertSame(20_000, $this->pricingService->getCurrentPrice($product));
    }

    /**
     * A listing prices every card from one discountables query and one
     * discounts query, however many products it shows.
     */
    public function test_it_loads_discounts_for_a_whole_collection_in_bulk(): void
    {
        $products = collect([
            $this->createProduct(),
            $this->createProduct(),
            $this->createProduct(),
            $this->createProduct(),
        ]);

        $discount = Discount::factory()->percentage(10)->create();
        $discount->products()->attach($products->first());

        DB::flushQueryLog();
        DB::enableQueryLog();

        $cards = app(ProductCardAssembler::class)->fromProducts($products);

        $queries = collect(DB::getQueryLog())->pluck('query');

        DB::disableQueryLog();

        static::assertCount(4, $cards);

        static::assertCount(
            1,
            $queries->filter(
                static fn (string $query): bool => str_contains($query, 'discountables'),
            ),
            'The discountables were read more than once for a collection.',
        );

        static::assertCount(
            1,
            $queries->filter(
                static fn (string $query): bool => str_contains(
                    $query,
                    'from "discounts"',
                ),
            ),
            'The discounts were read more than once for a collection.',
        );

        static::assertSame(18_000, $cards->first()?->currentPrice->toFils());
        static::assertSame(20_000, $cards->last()?->currentPrice->toFils());
    }

    private function createProduct(int $priceInFils = 20_000): Product
    {
        return Product::factory()
            ->active()
            ->create([
                'price_in_fils' => $priceInFils,
            ]);
    }
}
