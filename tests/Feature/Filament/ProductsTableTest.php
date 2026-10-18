<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\SemanticColor;
use App\Enums\StockStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Type;
use App\Models\User;
use App\Services\Catalog\ProductPricingService;
use App\Support\Money;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ProductsTableTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_products_can_be_grouped_by_their_category(): void
    {
        $this->actingAsAdmin();

        $category = Category::factory()->create(['name_en' => 'Laptops']);
        $this->createProductFor($category);

        Livewire::test(ListProducts::class)
            ->set('tableGrouping', 'type.category.name_en:asc')
            ->assertOk()
            ->assertSee('fi-ta-group-heading', false)
            ->assertSee('Laptops');
    }

    public function test_products_can_be_sorted_by_their_category(): void
    {
        $this->actingAsAdmin();

        $this->createProductFor(
            Category::factory()->create(['name_en' => 'Laptops']),
        );

        Livewire::test(ListProducts::class)
            ->sortTable('type.category.name_en')
            ->assertOk();
    }

    public function test_products_can_be_searched_by_their_category(): void
    {
        $this->actingAsAdmin();

        $laptop = $this->createProductFor(
            Category::factory()->create(['name_en' => 'Laptops']),
        );

        $this->createProductFor(
            Category::factory()->create(['name_en' => 'Cameras']),
        );

        Livewire::test(ListProducts::class)
            ->searchTable('Laptops')
            ->assertOk()
            ->assertCanSeeTableRecords([$laptop])
            ->assertCountTableRecords(1);
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    }

    private function createProductFor(Category $category): Product
    {
        $type = Type::factory()->create(['category_id' => $category->id]);

        return Product::factory()->create(['type_id' => $type->id]);
    }

    public function test_products_can_be_sorted_by_available_stock(): void
    {
        $this->actingAsAdmin();

        Product::factory()
            ->count(3)
            ->create([
                'stock' => 10,
                'reserved_stock' => 0,
            ]);

        Livewire::test(ListProducts::class)
            ->sortTable('available_stock')
            ->assertOk();
    }

    public function test_products_can_be_filtered_by_stock_status(): void
    {
        $this->actingAsAdmin();

        $outOfStock = Product::factory()->create([
            'stock' => 0,
            'reserved_stock' => 0,
            'low_stock_threshold' => 5,
        ]);
        $lowStock = Product::factory()->create([
            'stock' => 3,
            'reserved_stock' => 0,
            'low_stock_threshold' => 5,
        ]);
        $inStock = Product::factory()->create([
            'stock' => 100,
            'reserved_stock' => 0,
            'low_stock_threshold' => 5,
        ]);

        Livewire::test(ListProducts::class)
            ->filterTable('stock_status', StockStatus::LOW_STOCK->value)
            ->assertCanSeeTableRecords([$lowStock])
            ->assertCanNotSeeTableRecords([$outOfStock, $inStock]);
    }

    public function test_discounted_products_do_not_run_a_query_per_row(): void
    {
        $this->actingAsAdmin();

        Product::factory()->count(5)->discounted()->create();

        DB::enableQueryLog();
        DB::flushQueryLog();

        Livewire::test(ListProducts::class)->assertOk();

        $pivotQueries = collect(DB::getQueryLog())
            ->filter(
                static fn (array $query): bool => str_contains(
                    $query['query'],
                    'discountables',
                ),
            )
            ->count();

        DB::disableQueryLog();

        static::assertLessThanOrEqual(1, $pivotQueries);
    }

    public function test_the_discount_price_column_agrees_with_the_storefront_price(): void
    {
        $this->actingAsAdmin();

        $product = Product::factory()->create(['price_in_fils' => 20_000]);

        // A larger expired discount must not be counted; only the active one
        // prices the product, and the column must read the same price.
        Discount::factory()
            ->percentage(30)
            ->expired()
            ->create()
            ->products()
            ->attach($product);
        $active = Discount::factory()->percentage(20)->create();
        $active->products()->attach($product);

        $expected = Money::fromFils(
            app(ProductPricingService::class)->getCurrentPrice($product),
        )->toAmount();

        static::assertSame(16.0, $expected);

        Livewire::test(ListProducts::class)
            ->assertOk()
            ->assertTableColumnStateSet('discount_price', $expected, $product);
    }

    public function test_switch_action_reflects_the_record_state(): void
    {
        $this->actingAsAdmin();

        $active = Product::factory()->create(['activated_at' => now()]);
        $inactive = Product::factory()->create(['activated_at' => null]);

        Livewire::test(ListProducts::class)
            ->assertActionHasLabel(
                TestAction::make('activated_at')->table($active),
                ProductResource::trans('actions.deactivate'),
            )
            ->assertActionHasColor(
                TestAction::make('activated_at')->table($active),
                SemanticColor::Danger->value,
            )
            ->assertActionHasLabel(
                TestAction::make('activated_at')->table($inactive),
                ProductResource::trans('actions.activate'),
            )
            ->assertActionHasColor(
                TestAction::make('activated_at')->table($inactive),
                SemanticColor::Success->value,
            );
    }

    public function test_switch_action_switches_the_record(): void
    {
        $this->actingAsAdmin();

        $product = Product::factory()->create(['activated_at' => null]);

        Livewire::test(ListProducts::class)
            ->callAction(TestAction::make('activated_at')->table($product))
            ->assertNotified(ProductResource::trans('notifications.activated'));

        static::assertTrue($product->refresh()->isActive());
    }

    public function test_restock_adds_stock_to_a_product(): void
    {
        $this->actingAsAdmin();

        $product = Product::factory()->create(['stock' => 10]);

        Livewire::test(ListProducts::class)
            ->callAction(TestAction::make('restock')->table($product), ['quantity' => 5])
            ->assertNotified();

        static::assertSame(15, $product->refresh()->stock);
    }

    public function test_bulk_activate_activates_every_selected_product(): void
    {
        $this->actingAsAdmin();

        $products = Product::factory()
            ->count(2)
            ->create(['activated_at' => null]);

        Livewire::test(ListProducts::class)
            ->selectTableRecords($products->modelKeys())
            ->callAction(TestAction::make('activate')->table()->bulk())
            ->assertNotified(ProductResource::trans('notifications.activated'));

        foreach ($products as $product) {
            static::assertTrue($product->refresh()->isActive());
        }
    }

    public function test_bulk_deactivate_deactivates_every_selected_product(): void
    {
        $this->actingAsAdmin();

        $products = Product::factory()
            ->count(2)
            ->create(['activated_at' => now()->subDay()]);

        Livewire::test(ListProducts::class)
            ->selectTableRecords($products->modelKeys())
            ->callAction(TestAction::make('deactivate')->table()->bulk())
            ->assertNotified(ProductResource::trans('notifications.deactivated'));

        foreach ($products as $product) {
            static::assertFalse($product->refresh()->isActive());
        }
    }

    public function test_bulk_feature_features_every_selected_product(): void
    {
        $this->actingAsAdmin();

        $products = Product::factory()
            ->count(2)
            ->create(['featured_at' => null]);

        Livewire::test(ListProducts::class)
            ->selectTableRecords($products->modelKeys())
            ->callAction(TestAction::make('feature')->table()->bulk())
            ->assertNotified(ProductResource::trans('notifications.featured'));

        foreach ($products as $product) {
            static::assertTrue($product->refresh()->isFeatured());
        }
    }

    public function test_bulk_unfeature_unfeatures_every_selected_product(): void
    {
        $this->actingAsAdmin();

        $products = Product::factory()
            ->count(2)
            ->create(['featured_at' => now()->subDay()]);

        Livewire::test(ListProducts::class)
            ->selectTableRecords($products->modelKeys())
            ->callAction(TestAction::make('unfeature')->table()->bulk())
            ->assertNotified(ProductResource::trans('notifications.unfeatured'));

        foreach ($products as $product) {
            static::assertFalse($product->refresh()->isFeatured());
        }
    }
}
