<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Services\Catalog\CategoryService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * The categories the homepage rail offers: the top level only, in the order the
 * admin set, and no more than fit in the row.
 */
class CategoryServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private CategoryService $categoryService;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryService = app(CategoryService::class);
    }

    public function test_the_rail_is_the_active_top_level_categories_in_order(): void
    {
        $second = Category::factory()
            ->active()
            ->create([
                'parent_id' => null,
                'sort_order' => 2,
            ]);
        $first = Category::factory()
            ->active()
            ->create([
                'parent_id' => null,
                'sort_order' => 1,
            ]);

        // Neither a child of a category nor a withdrawn one belongs in a rail
        // of the ways into the catalogue.
        Category::factory()
            ->active()
            ->create([
                'parent_id' => $first->id,
                'sort_order' => 0,
            ]);
        Category::factory()->create([
            'parent_id' => null,
            'sort_order' => 0,
            'activated_at' => now()->addDay(),
        ]);

        $categories = $this->categoryService->getHomePageCategories();

        static::assertSame(
            [$first->id, $second->id],
            $categories->pluck('id')->all(),
        );
    }

    public function test_the_rail_holds_no_more_than_it_is_asked_for(): void
    {
        foreach (range(1, 3) as $position) {
            Category::factory()
                ->active()
                ->create([
                    'parent_id' => null,
                    'sort_order' => $position,
                ]);
        }

        // Everything there is, when there is less than a row holds…
        static::assertCount(3, $this->categoryService->getHomePageCategories());

        // …and no more than was asked for when there is more.
        static::assertCount(
            2,
            $this->categoryService->getHomePageCategories(2),
        );
    }

    public function test_the_rail_carries_the_thumbnail_its_cards_show(): void
    {
        Category::factory()->active()->create(['parent_id' => null]);

        $categories = $this->categoryService->getHomePageCategories();

        // A category card reads the category's thumbnail, so it is eager loaded
        // rather than read back one category at a time.
        static::assertTrue($categories->first()->relationLoaded('media'));
    }

    public function test_a_category_that_was_never_activated_is_not_active(): void
    {
        // activated_at is not nullable in the table, so a null one can only be
        // read in memory: the model must still agree with the active() scope and
        // answer false rather than comparing null against now().
        $category = new Category(['activated_at' => null]);

        static::assertFalse($category->isActive());
    }
}
