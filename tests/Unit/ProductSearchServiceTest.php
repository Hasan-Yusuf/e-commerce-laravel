<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Product;
use App\Services\Catalog\ProductSearchService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProductSearchServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private ProductSearchService $search;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->search = app(ProductSearchService::class);
    }

    public function test_normalizes_raw_search_input(): void
    {
        static::assertSame(
            'keyboard',
            $this->search->normalize('  keyboard  '),
        );
        static::assertSame('', $this->search->normalize('a'));
        static::assertSame('', $this->search->normalize(''));
        static::assertSame('', $this->search->normalize('   '));
        static::assertSame('', $this->search->normalize(['x']));
        static::assertSame('', $this->search->normalize(null));
    }

    public function test_empty_term_leaves_the_query_untouched(): void
    {
        Product::factory()->active()->count(2)->create();

        static::assertSame(
            2,
            $this->search->apply(Product::query(), '')->count(),
        );
    }

    public function test_over_long_term_matches_nothing(): void
    {
        Product::factory()->active()->create(['name' => 'Keyboard']);

        static::assertSame(
            0,
            $this->search
                ->apply(
                    Product::query(),
                    str_repeat('a', ProductSearchService::MAX_SEARCH_LENGTH + 1),
                )
                ->count(),
        );
    }

    public function test_like_wildcards_are_matched_literally(): void
    {
        Product::factory()
            ->active()
            ->create(['name' => '100% Cotton Desk']);
        Product::factory()
            ->active()
            ->create(['name' => 'Plain Plastic Desk']);

        static::assertSame(
            1,
            $this->search->apply(Product::query(), '100%')->count(),
        );
    }
}
