<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\SpecValueType;
use App\Enums\UserRole;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Filament\Resources\Products\RelationManagers\DiscountRelationManager;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Type;
use App\Models\TypeSpec;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Field;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin panel coverage for the Products resource.
 *
 * The form is a two-step Wizard. Only plain fields are filled here; the required
 * `Thumbnail` upload is passed as a fake uploaded file because there is no
 * factory state that attaches a thumbnail to a product.
 */
class ProductsResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_lists_products(): void
    {
        $this->actingAsAdmin();

        $products = Product::factory()->count(3)->create();

        Livewire::test(ListProducts::class)
            ->assertOk()
            ->assertCanSeeTableRecords($products);
    }

    public function test_it_requires_the_mandatory_fields_to_create_a_product(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => null,
                'slug' => null,
                'sku' => null,
                'description_en' => null,
                'price_in_fils' => null,
                'cost_price_in_fils' => null,
                'type_id' => null,
                'Thumbnail' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'sku' => 'required',
                'description_en' => 'required',
                'price_in_fils' => 'required',
                'cost_price_in_fils' => 'required',
                'type_id' => 'required',
                'Thumbnail' => 'required',
            ])
            ->assertNotNotified();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_creates_a_product(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');

        $type = Type::factory()->create();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Test Product',
                'slug' => 'test-product',
                'sku' => 'TEST-SKU-1',
                'description_en' => 'An English description.',
                'price_in_fils' => '12.5',
                'cost_price_in_fils' => '8.0',
                'Thumbnail' => UploadedFile::fake()->image('thumbnail.webp'),
                'type_id' => $type->id,
                'specs_en' => $this->specValues($type, 'en'),
                'specs_ar' => $this->specValues($type, 'ar'),
                'stock' => 10,
                'low_stock_threshold' => 5,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('products', [
            'slug' => 'test-product',
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-1',
            'price_in_fils' => 12_500,
        ]);

        // The form's toggles start on for Active and off for Featured, and what
        // is saved has to say the same: a product that is on sale as soon as it
        // is created, and promoted only when the administrator says so.
        $product = Product::query()->where('slug', 'test-product')->sole();

        static::assertNotNull($product->activated_at);
        static::assertTrue($product->isActive());
        static::assertNull($product->featured_at);
    }

    public function test_it_stores_typed_spec_values_from_the_selected_type(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');

        $type = Type::factory()->create();

        $type->specDefinitions()->delete();

        TypeSpec::factory()
            ->for($type)
            ->number('GB')
            ->create([
                'name_en' => 'Memory',
                'name_ar' => 'الذاكرة',
                'sort_order' => 0,
            ]);

        TypeSpec::factory()
            ->for($type)
            ->options([
                ['label_en' => 'Black', 'label_ar' => 'أسود'],
            ])
            ->create([
                'name_en' => 'Colour',
                'name_ar' => 'اللون',
                'sort_order' => 1,
            ]);

        TypeSpec::factory()
            ->for($type)
            ->boolean()
            ->create([
                'name_en' => 'RGB',
                'name_ar' => 'إضاءة RGB',
                'sort_order' => 2,
            ]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Test Product',
                'slug' => 'test-product',
                'sku' => 'TEST-SKU-1',
                'description_en' => 'An English description.',
                'price_in_fils' => '12.5',
                'cost_price_in_fils' => '8.0',
                'Thumbnail' => UploadedFile::fake()->image('thumbnail.webp'),
                'type_id' => $type->id,
                'specs_en' => [
                    'Memory' => '16',
                    'Colour' => 'Black',
                    'RGB' => true,
                ],
                // Only the localized value has an Arabic input of its own; the
                // number and the yes/no are captured once and mirrored on save.
                'specs_ar' => [
                    'اللون' => 'أسود',
                ],
                'stock' => 10,
                'low_stock_threshold' => 5,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $product = Product::firstWhere('slug', 'test-product');

        static::assertSame(
            ['Memory' => 16, 'Colour' => 'Black', 'RGB' => true],
            $product->specs_en,
        );
        static::assertSame(
            ['الذاكرة' => 16, 'اللون' => 'أسود', 'إضاءة RGB' => true],
            $product->specs_ar,
        );
    }

    public function test_a_number_spec_that_declares_its_values_is_picked_from(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');

        $type = Type::factory()->create();

        $type->specDefinitions()->delete();

        TypeSpec::factory()
            ->for($type)
            ->number('GB')
            ->create([
                'name_en' => 'Memory',
                'name_ar' => 'الذاكرة',
                'options' => [
                    ['label_en' => '8', 'label_ar' => '8'],
                    ['label_en' => '16', 'label_ar' => '16'],
                ],
                'sort_order' => 0,
            ]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Test Product',
                'slug' => 'test-product',
                'sku' => 'TEST-SKU-1',
                'description_en' => 'An English description.',
                'price_in_fils' => '12.5',
                'cost_price_in_fils' => '8.0',
                'Thumbnail' => UploadedFile::fake()->image('thumbnail.webp'),
                'type_id' => $type->id,
                'specs_en' => ['Memory' => '16'],
                'stock' => 10,
                'low_stock_threshold' => 5,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $product = Product::firstWhere('slug', 'test-product');

        // One of the values the spec declared, stored as the number it names and
        // mirrored into the locale that has no input of its own.
        static::assertSame(['Memory' => 16], $product->specs_en);
        static::assertSame(['الذاكرة' => 16], $product->specs_ar);
    }

    public function test_it_mirrors_a_single_input_spec_when_editing_a_product(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');

        $type = Type::factory()->create();

        $type->specDefinitions()->delete();

        TypeSpec::factory()
            ->for($type)
            ->number('GB')
            ->create([
                'name_en' => 'Memory',
                'name_ar' => 'الذاكرة',
                'sort_order' => 0,
            ]);

        $product = Product::factory()->create([
            'type_id' => $type->id,
            'specs_en' => ['Memory' => 8],
            'specs_ar' => ['الذاكرة' => 8],
        ]);

        Livewire::test(EditProduct::class, [
            'record' => $product->getRouteKey(),
        ])
            ->fillForm([
                'specs_en' => ['Memory' => '32'],
                'Thumbnail' => UploadedFile::fake()->image('thumbnail.webp'),
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $product->refresh();

        static::assertSame(['Memory' => 32], $product->specs_en);
        static::assertSame(['الذاكرة' => 32], $product->specs_ar);
    }

    public function test_a_locale_independent_spec_is_captured_with_a_single_input(): void
    {
        $this->actingAsAdmin();

        // TypeFactory's schema: Memory (number), Colour (options), Warranty
        // (text), RGB (boolean).
        $type = Type::factory()->create();

        Livewire::test(CreateProduct::class)
            ->fillForm(['type_id' => $type->id])
            ->assertFormFieldExists('specs_en.Memory')
            ->assertFormFieldDoesNotExist('specs_ar.الذاكرة')
            ->assertFormFieldExists('specs_en.RGB')
            ->assertFormFieldDoesNotExist('specs_ar.إضاءة RGB')
            ->assertFormFieldExists('specs_en.Colour')
            ->assertFormFieldExists('specs_ar.اللون')
            ->assertFormFieldExists('specs_en.Warranty')
            ->assertFormFieldExists('specs_ar.الضمان')
            ->assertFormFieldExists(
                'specs_en.Memory',
                static fn (Field $field): bool => $field->getColumnSpan('lg') === 'full',
            )
            ->assertFormFieldExists(
                'specs_en.Colour',
                static fn (Field $field): bool => $field->getColumnSpan('lg') === 1,
            )
            ->assertFormFieldExists(
                'specs_ar.اللون',
                static fn (Field $field): bool => $field->getColumnSpan('lg') === 1,
            );
    }

    public function test_choosing_an_option_answers_its_twin_in_the_other_locale(): void
    {
        $this->actingAsAdmin();

        // TypeFactory's Colour spec offers Black/أسود and Silver/فضي.
        $type = Type::factory()->create();

        Livewire::test(CreateProduct::class)
            ->fillForm(['type_id' => $type->id])
            ->set('data.specs_en.Colour', 'Silver')
            ->assertSchemaStateSet(['specs_ar.اللون' => 'فضي'])
            // The two locales are one list, so answering Arabic answers English.
            ->set('data.specs_ar.اللون', 'أسود')
            ->assertSchemaStateSet(['specs_en.Colour' => 'Black'])
            // Clearing a choice clears the one that answered it.
            ->set('data.specs_en.Colour', null)
            ->assertSchemaStateSet(['specs_ar.اللون' => null]);
    }

    public function test_it_edits_a_product(): void
    {
        $this->actingAsAdmin();
        Storage::fake('public');

        $product = Product::factory()->create();

        Livewire::test(EditProduct::class, [
            'record' => $product->getRouteKey(),
        ])
            ->fillForm([
                'name' => 'Updated Product',
                'Thumbnail' => UploadedFile::fake()->image('thumbnail.webp'),
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
        ]);
    }

    public function test_it_renders_the_view_page(): void
    {
        $this->actingAsAdmin();

        $product = Product::factory()->create();

        Livewire::test(ViewProduct::class, [
            'record' => $product->getRouteKey(),
        ])
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_it_deletes_a_product(): void
    {
        $this->actingAsAdmin();

        $product = Product::factory()->create();

        Livewire::test(ListProducts::class)
            ->callAction(TestAction::make('delete')->table($product))
            ->assertNotified();

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    }

    public function test_it_lists_the_discounts_attached_to_a_product(): void
    {
        $this->actingAsAdmin();

        $product = Product::factory()->create();

        $discount = Discount::factory()->create();
        $discount->products()->attach($product);

        Livewire::test(DiscountRelationManager::class, [
            'ownerRecord' => $product,
            'pageClass' => ViewProduct::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords([$discount]);
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    }

    /**
     * A valid value for every spec the form shows in the locale: the English
     * input always exists, an Arabic one only for a localized value.
     *
     * @return array<string, string|int|bool>
     */
    private function specValues(Type $type, string $locale): array
    {
        return $type
            ->specDefinitions
            ->filter(
                static fn (TypeSpec $spec): bool => $spec->value_type->isLocalized() || $locale === 'en',
            )
            ->mapWithKeys(static function (TypeSpec $spec) use ($locale): array {
                $value = match ($spec->value_type) {
                    SpecValueType::NUMBER => '16',
                    SpecValueType::BOOLEAN => true,
                    SpecValueType::OPTIONS => $spec->optionLabels($locale)[0],
                    default => 'Value',
                };

                return [$spec->nameFor($locale) => $value];
            })
            ->all();
    }
}
