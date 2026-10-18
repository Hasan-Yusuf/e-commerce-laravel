<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SpecFilterType;
use App\Enums\SpecValueType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use Database\Seeders\BrandSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\TypeSeeder;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FreshCatalogMigrationTest extends TestCase
{
    public function test_fresh_migrations_and_seed_produce_a_usable_catalog(): void
    {
        $this->seedCatalog();

        static::assertGreaterThan(0, Product::count());
        static::assertGreaterThan(0, Type::count());
        static::assertGreaterThan(0, Category::count());
        static::assertGreaterThan(0, Brand::count());

        // Pictures are opt-in (see config/seeders.php): each one becomes a WebP
        // conversion with responsive variants, so the seed writes rows, not media.
        $this->assertDatabaseCount('media', 0);

        $product = Product::query()->first();

        static::assertNotNull($product?->type);
        static::assertNotNull($product->category);
        static::assertNotNull($product->brand);
        static::assertGreaterThanOrEqual(0, $product->getAvailableStock());
    }

    public function test_the_products_table_is_indexed_per_filtered_and_sorted_column(): void
    {
        $this->artisan('migrate:fresh')->assertExitCode(0);

        $indexedColumns = collect(Schema::getIndexes('products'))->map(
            static fn (array $index): array => $index['columns'],
        )
            ->values();

        foreach ([
            'activated_at',
            'type_id',
            'brand_id',
            'featured_at',
            'price_in_fils',
        ] as $column) {
            static::assertTrue(
                $indexedColumns->contains(
                    static fn (array $columns): bool => $columns === [$column],
                ),
                "The products table has no single-column index on [{$column}].",
            );
        }

        static::assertFalse(
            $indexedColumns->contains(
                static fn (array $columns): bool => count($columns) === 7,
            ),
            'The seven-column composite products index is still present.',
        );
    }

    public function test_seeded_types_carry_typed_filterable_specs(): void
    {
        $this->seedCatalog();

        $type = Type::query()
            ->where('name', 'Smartphones')
            ->firstOrFail();

        $storage = $type->specDefinitions
            ->firstWhere('name_en', 'Storage');

        static::assertNotNull($storage);
        static::assertSame(SpecValueType::NUMBER, $storage->value_type);
        static::assertSame('GB', $storage->unit);
        static::assertTrue($storage->is_filterable);
        static::assertSame(SpecFilterType::RANGE, $storage->filter_type);
        static::assertTrue($storage->usesRangeFilter());

        $operatingSystem = $type->specDefinitions
            ->firstWhere('name_en', 'Operating System');

        static::assertNotNull($operatingSystem);
        static::assertSame(SpecValueType::TEXT, $operatingSystem->value_type);
        static::assertFalse($operatingSystem->is_filterable);
        static::assertNull($operatingSystem->filter_type);
    }

    public function test_seeded_products_store_values_shaped_like_their_spec(): void
    {
        $this->seedCatalog();

        $smartphone = Product::query()
            ->whereHas(
                'type.specDefinitions',
                static fn ($query) => $query->where('name_en', 'Storage'),
            )
            ->whereNotNull('specs_en')
            ->firstOrFail();

        static::assertIsInt($smartphone->specs_en['Storage']);

        $chair = Product::query()
            ->whereHas(
                'type.specDefinitions',
                static fn ($query) => $query->where('name_en', 'Material'),
            )
            ->firstOrFail();

        static::assertIsString($chair->specs_en['Material']);

        $headphones = Product::query()
            ->whereHas(
                'type.specDefinitions',
                static fn ($query) => $query->where('name_en', 'Noise Cancelling')
                    ->where('value_type', SpecValueType::BOOLEAN),
            )
            ->firstOrFail();

        static::assertIsBool($headphones->specs_en['Noise Cancelling']);
    }

    public function test_seeded_products_are_keyed_by_their_types_localized_spec_names(): void
    {
        $this->seedCatalog();

        $products = Product::query()
            ->with('type.specDefinitions')
            ->whereNotNull('specs_en')
            ->get();

        static::assertGreaterThan(0, $products->count());

        foreach ($products as $product) {
            static::assertSame(
                $product->type->specDefinitions->pluck('name_en')->all(),
                array_keys($product->specs_en),
                "The English specs of [{$product->slug}] are out of step with their type.",
            );

            static::assertSame(
                $product->type->specDefinitions->pluck('name_ar')->all(),
                array_keys($product->specs_ar),
                "The Arabic specs of [{$product->slug}] are out of step with their type.",
            );
        }
    }

    public function test_the_seeded_storefront_filters_by_its_typed_specs(): void
    {
        $this->seedCatalog();

        $category = Category::query()
            ->where('slug', 'smartphones')
            ->firstOrFail();

        // The seeded range specs draw a min/max pair, titled with their unit.
        $this
            ->get(route('category.show', $category->slug))
            ->assertOk()
            ->assertSee('Storage (GB)')
            ->assertSee('Screen Size (in)');

        $type = Type::query()
            ->where('name', 'Smartphones')
            ->firstOrFail();

        $storage = $type->specDefinitions->firstWhere('name_en', 'Storage');

        // 512GB is the largest seeded phone; the 128GB model drops out.
        $this
            ->get(route('category.show', [
                'category' => $category->slug,
                'specs' => [$storage->id => ['min' => 256]],
            ]))
            ->assertOk()
            ->assertSee('Nova X5 Pro')
            ->assertDontSee('Solstice S10 Lite');

        // The same spec reads and filters in Arabic, against the Arabic names
        // its values are stored under.
        $this
            ->withSession(['locale' => 'ar'])
            ->get(route('category.show', [
                'category' => $category->slug,
                'specs' => [$storage->id => ['min' => 256]],
            ]))
            ->assertOk()
            ->assertSee('التخزين (GB)')
            ->assertSee('Nova X5 Pro')
            ->assertDontSee('Solstice S10 Lite');
    }

    /**
     * Rebuild the schema and seed only the catalogue, which is all these tests
     * read. The full DatabaseSeeder also lays down banners (which fetch their
     * images over the network), users, addresses, orders and payments — none of
     * which a catalogue assertion needs.
     */
    private function seedCatalog(): void
    {
        $this->artisan('migrate:fresh')->assertExitCode(0);

        $this->seed([
            BrandSeeder::class,
            CategorySeeder::class,
            TypeSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
