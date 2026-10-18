<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\Types\Pages\EditType;
use App\Filament\Resources\Types\Pages\ListTypes;
use App\Filament\Resources\Types\Pages\UpdateTypeProducts;
use App\Filament\Resources\Types\TypeResource;
use App\Models\Product;
use App\Models\Type;
use App\Models\User;
use App\Services\Catalog\ProductSpecSyncService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The type's products and the type's specs have to tell the same story: the
 * specs are the schema the products answer for, and the answers live on the
 * products. Editing a spec therefore sends the admin through the type's
 * products, and a rename carries the existing answers over rather than leaving
 * them behind under a name nothing reads any more.
 */
class UpdateTypeProductsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_renaming_a_specification_carries_the_values_over_and_asks_for_a_review(): void
    {
        $this->actingAsAdmin();

        [$type, $product] = $this->typeWithProduct();
        $memory = $type->specDefinitions->firstWhere('name_en', 'Memory');

        Livewire::test(EditType::class, ['record' => $type->getRouteKey()])
            ->set("data.specDefinitions.record-{$memory->id}.name_en", 'RAM')
            ->set("data.specDefinitions.record-{$memory->id}.name_ar", 'رام')
            ->call('save')
            ->assertHasNoFormErrors()
            // The products are where the admin goes next, not the list.
            ->assertRedirect(TypeResource::getUrl('products', [
                'record' => $type,
            ]));

        $product->refresh();

        // The value follows the spec to its new name, in both locales.
        static::assertSame(16, $product->specs_en['RAM']);
        static::assertArrayNotHasKey('Memory', $product->specs_en);
        static::assertSame(16, $product->specs_ar['رام']);
        static::assertArrayNotHasKey('الذاكرة', $product->specs_ar);

        static::assertTrue($type->refresh()->needsSpecReview());
    }

    public function test_editing_a_type_without_touching_its_specifications_leaves_the_products_alone(): void
    {
        $this->actingAsAdmin();

        [$type, $product] = $this->typeWithProduct();
        $memory = $type->specDefinitions->firstWhere('name_en', 'Memory');

        Livewire::test(EditType::class, ['record' => $type->getRouteKey()])
            ->fillForm(['name' => 'Updated Type Name'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $type->refresh();

        // A save that left the specs alone does not rewrite the spec rows...
        static::assertSame(
            $memory->id,
            $type->specDefinitions->firstWhere('name_en', 'Memory')?->id,
            'The spec row was rewritten.',
        );

        $this->assertDatabaseHas('types', [
            'id' => $type->id,
            'name' => 'Updated Type Name',
            'specs_need_review' => false,
        ]);

        // ...nor ask the admin to go through the products again.
        static::assertSame(
            ['Memory' => 16, 'Colour' => 'Black', 'Warranty' => '1 year', 'RGB' => true],
            $product->refresh()->specs_en,
        );
    }

    public function test_a_specification_change_on_a_type_without_products_asks_for_no_review(): void
    {
        $this->actingAsAdmin();

        $type = Type::factory()->create();
        $memory = $type->specDefinitions->firstWhere('name_en', 'Memory');

        Livewire::test(EditType::class, ['record' => $type->getRouteKey()])
            ->set("data.specDefinitions.record-{$memory->id}.name_en", 'RAM')
            ->call('save')
            ->assertHasNoFormErrors();

        static::assertFalse($type->refresh()->needsSpecReview());
    }

    public function test_the_edit_page_offers_the_products_action_while_they_need_review(): void
    {
        $this->actingAsAdmin();

        [$type] = $this->typeWithProduct();

        Livewire::test(EditType::class, ['record' => $type->getRouteKey()])
            ->assertActionHidden('updateProducts');

        $this->flagForReview($type);

        Livewire::test(EditType::class, ['record' => $type->getRouteKey()])
            ->assertActionVisible('updateProducts');
    }

    public function test_the_types_table_offers_the_products_update_as_a_row_action(): void
    {
        $this->actingAsAdmin();

        [$type] = $this->typeWithProduct();

        Livewire::test(ListTypes::class)
            ->call('loadTable')
            ->assertActionHidden(TestAction::make('updateProducts')->table($type));

        $this->flagForReview($type);

        Livewire::test(ListTypes::class)
            ->call('loadTable')
            ->assertActionVisible(TestAction::make('updateProducts')->table($type))
            ->assertCanSeeTableRecords([$type]);
    }

    public function test_the_products_page_rebuilds_every_value_from_the_specifications(): void
    {
        $this->actingAsAdmin();

        [$type, $product] = $this->typeWithProduct();

        $this->flagForReview($type);

        Livewire::test(UpdateTypeProducts::class, [
            'record' => $type->getRouteKey(),
        ])
            ->fillForm([
                'products' => [
                    $product->id => [
                        'specs_en' => [
                            'Memory' => '32',
                            'Colour' => 'Silver',
                            'Warranty' => '3 years',
                            // A yes/no is the one value that reads the same
                            // everywhere and is captured once.
                            'RGB' => false,
                        ],
                        // The two localized specs are answered themselves.
                        'specs_ar' => [
                            'اللون' => 'فضي',
                            'الضمان' => 'ثلاث سنوات',
                        ],
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect(TypeResource::getUrl('edit', ['record' => $type]));

        $product->refresh();

        // In schema order, with the mirrored locales rebuilt from the same value.
        static::assertSame(
            [
                'Memory' => 32,
                'Colour' => 'Silver',
                'Warranty' => '3 years',
                'RGB' => false,
            ],
            $product->specs_en,
        );
        static::assertSame(
            [
                'الذاكرة' => 32,
                'اللون' => 'فضي',
                'الضمان' => 'ثلاث سنوات',
                'إضاءة RGB' => false,
            ],
            $product->specs_ar,
        );

        // The products have been gone through, so nothing is waiting any more.
        static::assertFalse($type->refresh()->needsSpecReview());
    }

    public function test_the_products_page_drops_the_values_of_a_specification_the_type_no_longer_declares(): void
    {
        $this->actingAsAdmin();

        [$type, $product] = $this->typeWithProduct();

        $product->update([
            'specs_en' => [...$product->specs_en, 'Old Spec' => 'Left behind'],
            'specs_ar' => [...$product->specs_ar, 'مواصفة قديمة' => 'Left behind'],
        ]);

        $this->flagForReview($type);

        Livewire::test(UpdateTypeProducts::class, [
            'record' => $type->getRouteKey(),
        ])
            ->fillForm([
                'products' => [
                    $product->id => [
                        'specs_en' => [
                            'Memory' => '8',
                            'Colour' => 'Black',
                            'Warranty' => '1 year',
                        ],
                        'specs_ar' => [
                            'اللون' => 'أسود',
                            'الضمان' => 'سنة',
                        ],
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();

        static::assertArrayNotHasKey('Old Spec', $product->specs_en);
        static::assertArrayNotHasKey('مواصفة قديمة', $product->specs_ar);
    }

    /**
     * The factory's four-spec schema (Memory, Colour, Warranty, RGB) and one
     * product filled in against it.
     */
    private function typeWithProduct(): array
    {
        $type = Type::factory()->create();

        $product = Product::factory()->create([
            'type_id' => $type->id,
            'specs_en' => [
                'Memory' => 16,
                'Colour' => 'Black',
                'Warranty' => '1 year',
                'RGB' => true,
            ],
            'specs_ar' => [
                'الذاكرة' => 16,
                'اللون' => 'أسود',
                'الضمان' => 'سنة',
                'إضاءة RGB' => true,
            ],
        ]);

        return [$type, $product];
    }

    /**
     * The state a type is left in when its specs changed under its products.
     */
    private function flagForReview(Type $type): void
    {
        app(ProductSpecSyncService::class)->flagForReview($type);

        $type->refresh();
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    }
}
