<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\SpecFilterType;
use App\Enums\SpecValueType;
use App\Enums\UserRole;
use App\Filament\Resources\Types\Pages\CreateType;
use App\Filament\Resources\Types\Pages\EditType;
use App\Filament\Resources\Types\Pages\ListTypes;
use App\Models\Category;
use App\Models\Type;
use App\Models\TypeSpec;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin panel coverage for the Types resource.
 *
 * The `category.name_en` and `specs` columns search, sort, group, and filter
 * against the `type_specs` relationship, so those interactions are covered here.
 */
class TypesResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_lists_types(): void
    {
        $this->actingAsAdmin();

        $category = Category::factory()->create();

        $types = Type::factory()
            ->count(3)
            ->create(['category_id' => $category->id]);

        Livewire::test(ListTypes::class)
            ->call('loadTable')
            ->assertOk()
            ->assertCanSeeTableRecords($types);
    }

    public function test_types_can_be_searched_by_their_category_name(): void
    {
        $this->actingAsAdmin();

        $laptop = Type::factory()->create([
            'category_id' => Category::factory()->create([
                'name_en' => 'Laptops',
            ])->id,
        ]);

        $keyboard = Type::factory()->create([
            'category_id' => Category::factory()->create([
                'name_en' => 'Cameras',
            ])->id,
        ]);

        Livewire::test(ListTypes::class)
            ->call('loadTable')
            ->searchTable('Laptops')
            ->assertOk()
            ->assertCanSeeTableRecords([$laptop])
            ->assertCanNotSeeTableRecords([$keyboard])
            ->assertCountTableRecords(1);
    }

    public function test_types_can_be_sorted_by_their_category_name(): void
    {
        $this->actingAsAdmin();

        Type::factory()->create([
            'category_id' => Category::factory()->create([
                'name_en' => 'Laptops',
            ])->id,
        ]);

        Livewire::test(ListTypes::class)
            ->call('loadTable')
            ->sortTable('category.name_en')
            ->assertOk();
    }

    public function test_types_can_be_grouped_by_their_category_name(): void
    {
        $this->actingAsAdmin();

        Type::factory()->create([
            'category_id' => Category::factory()->create([
                'name_en' => 'Laptops',
            ])->id,
        ]);

        Livewire::test(ListTypes::class)
            ->call('loadTable')
            ->set('tableGrouping', 'category.name_en:asc')
            ->assertOk()
            ->assertSee('fi-ta-group-heading', false)
            ->assertSee('Laptops');
    }

    public function test_types_can_be_searched_by_their_specs(): void
    {
        $this->actingAsAdmin();

        $category = Category::factory()->create();

        $matching = $this->createTypeWithSpec($category, '16GB RAM');
        $nonMatching = $this->createTypeWithSpec($category, '8GB RAM');

        Livewire::test(ListTypes::class)
            ->call('loadTable')
            ->searchTable('16GB')
            ->assertOk()
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$nonMatching])
            ->assertCountTableRecords(1);
    }

    public function test_types_can_be_filtered_by_their_specs(): void
    {
        $this->actingAsAdmin();

        $category = Category::factory()->create();

        $matching = $this->createTypeWithSpec($category, '16GB RAM');
        $nonMatching = $this->createTypeWithSpec($category, '8GB RAM');

        Livewire::test(ListTypes::class)
            ->call('loadTable')
            ->filterTable('specs', '16GB RAM')
            ->assertOk()
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$nonMatching])
            ->assertCountTableRecords(1);
    }

    public function test_it_requires_the_mandatory_fields_to_create_a_type(): void
    {
        Repeater::fake();

        $this->actingAsAdmin();

        Livewire::test(CreateType::class)
            ->fillForm([
                'name' => null,
                'category_id' => null,
                'specDefinitions' => [
                    [
                        'name_en' => null,
                        'name_ar' => null,
                        'value_type' => SpecValueType::TEXT->value,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'category_id' => 'required',
                'specDefinitions.0.name_en' => 'required',
                'specDefinitions.0.name_ar' => 'required',
            ])
            ->assertNotNotified();

        $this->assertDatabaseCount('types', 0);
    }

    public function test_it_creates_a_type(): void
    {
        Repeater::fake();

        $this->actingAsAdmin();

        $category = Category::factory()->create();

        Livewire::test(CreateType::class)
            ->fillForm([
                'name' => 'Laptops',
                'category_id' => $category->id,
                'specDefinitions' => [
                    [
                        'name_en' => 'RAM',
                        'name_ar' => 'الذاكرة',
                        'value_type' => SpecValueType::TEXT->value,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('types', [
            'name' => 'Laptops',
            'category_id' => $category->id,
        ]);
    }

    public function test_it_persists_a_number_range_spec_with_its_unit(): void
    {
        Repeater::fake();

        $this->actingAsAdmin();

        $category = Category::factory()->create();

        Livewire::test(CreateType::class)
            ->fillForm([
                'name' => 'Laptops',
                'category_id' => $category->id,
                'specDefinitions' => [
                    [
                        'name_en' => 'Memory',
                        'name_ar' => 'الذاكرة',
                        'value_type' => SpecValueType::NUMBER->value,
                        'unit' => 'GB',
                        'is_filterable' => true,
                        'filter_type' => SpecFilterType::RANGE->value,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $type = Type::firstWhere('name', 'Laptops');

        $this->assertDatabaseHas('type_specs', [
            'type_id' => $type->id,
            'name_en' => 'Memory',
            'name_ar' => 'الذاكرة',
            'value_type' => SpecValueType::NUMBER->value,
            'unit' => 'GB',
            'is_filterable' => true,
            'filter_type' => SpecFilterType::RANGE->value,
        ]);
    }

    public function test_it_keeps_the_specs_in_their_repeater_order(): void
    {
        Repeater::fake();

        $this->actingAsAdmin();

        $category = Category::factory()->create();

        Livewire::test(CreateType::class)
            ->fillForm([
                'name' => 'Laptops',
                'category_id' => $category->id,
                'specDefinitions' => [
                    [
                        'name_en' => 'Memory',
                        'name_ar' => 'الذاكرة',
                        'value_type' => SpecValueType::NUMBER->value,
                    ],
                    [
                        'name_en' => 'Storage',
                        'name_ar' => 'التخزين',
                        'value_type' => SpecValueType::TEXT->value,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $type = Type::firstWhere('name', 'Laptops');

        static::assertSame(
            ['Memory', 'Storage'],
            $type->specDefinitions->pluck('name_en')->all(),
        );
    }

    public function test_it_persists_the_options_of_an_options_spec(): void
    {
        Repeater::fake();

        $this->actingAsAdmin();

        $category = Category::factory()->create();

        Livewire::test(CreateType::class)
            ->fillForm([
                'name' => 'Laptops',
                'category_id' => $category->id,
                'specDefinitions' => [
                    [
                        'name_en' => 'Colour',
                        'name_ar' => 'اللون',
                        'value_type' => SpecValueType::OPTIONS->value,
                        'options' => [
                            ['label_en' => 'Black', 'label_ar' => 'أسود'],
                            ['label_en' => 'Silver', 'label_ar' => 'فضي'],
                        ],
                        'is_filterable' => true,
                        'filter_type' => SpecFilterType::LIST->value,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $spec = TypeSpec::firstWhere('name_en', 'Colour');

        static::assertSame(SpecValueType::OPTIONS, $spec->value_type);
        static::assertSame(
            [
                ['label_en' => 'Black', 'label_ar' => 'أسود'],
                ['label_en' => 'Silver', 'label_ar' => 'فضي'],
            ],
            $spec->options,
        );
        static::assertSame(
            ['Black', 'Silver'],
            $spec->optionLabels('en'),
        );
    }

    public function test_the_spec_fields_are_shown_conditionally(): void
    {
        Repeater::fake();

        $this->actingAsAdmin();

        Livewire::test(CreateType::class)
            ->fillForm([
                'specDefinitions' => [
                    [
                        'name_en' => 'Memory',
                        'name_ar' => 'الذاكرة',
                        'value_type' => SpecValueType::NUMBER->value,
                        'unit' => 'GB',
                        'is_filterable' => true,
                        'filter_type' => SpecFilterType::RANGE->value,
                    ],
                    [
                        'name_en' => 'Colour',
                        'name_ar' => 'اللون',
                        'value_type' => SpecValueType::OPTIONS->value,
                        'options' => [
                            ['label_en' => 'Black', 'label_ar' => 'أسود'],
                        ],
                    ],
                ],
            ])
            // `unit` and a `range` filter are only valid for numbers.
            ->assertSchemaComponentVisible('specDefinitions.0.unit')
            ->assertSchemaComponentVisible('specDefinitions.0.filter_type')
            // A number spec may name the values it takes, the way RAM sizes are
            // fixed, so the same editor collects them.
            ->assertSchemaComponentVisible('specDefinitions.0.options')
            // The options editor only shows for the options value type.
            ->assertSchemaComponentVisible('specDefinitions.1.options')
            ->assertSchemaComponentHidden('specDefinitions.1.unit')
            // The filter type hides while the spec is not filterable.
            ->assertSchemaComponentHidden('specDefinitions.1.filter_type');
    }

    public function test_it_persists_the_values_a_number_spec_declares(): void
    {
        Repeater::fake();

        $this->actingAsAdmin();

        $category = Category::factory()->create();

        Livewire::test(CreateType::class)
            ->fillForm([
                'name' => 'Laptops',
                'category_id' => $category->id,
                'specDefinitions' => [
                    [
                        'name_en' => 'Memory',
                        'name_ar' => 'الذاكرة',
                        'value_type' => SpecValueType::NUMBER->value,
                        'unit' => 'GB',
                        'options' => [
                            ['label_en' => '8', 'label_ar' => '8'],
                            ['label_en' => '16', 'label_ar' => '16'],
                        ],
                        'is_filterable' => true,
                        'filter_type' => SpecFilterType::LIST->value,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $spec = TypeSpec::firstWhere('name_en', 'Memory');

        // The declared values are read back with the same labels an options spec
        // answers with, as the numbers they name.
        static::assertSame([8, 16], $spec->optionValues());
        static::assertTrue($spec->presetsValues());
        static::assertSame(['8', '16'], $spec->optionLabels('ar'));
    }

    public function test_a_number_specs_declared_value_is_entered_once(): void
    {
        Repeater::fake();

        $this->actingAsAdmin();

        Livewire::test(CreateType::class)
            ->fillForm([
                'name' => 'Laptops',
                'category_id' => Category::factory()->create()->id,
                'specDefinitions' => [
                    [
                        'name_en' => 'Memory',
                        'name_ar' => 'الذاكرة',
                        'value_type' => SpecValueType::NUMBER->value,
                        'options' => [
                            ['label_en' => '8', 'label_ar' => '8'],
                        ],
                    ],
                ],
            ])
            // A number reads the same in both locales, so the one input answers
            // the pair the value is stored as.
            ->set('data.specDefinitions.0.options.0.label_en', '16')
            ->assertSchemaStateSet(['specDefinitions.0.options.0.label_ar' => '16']);
    }

    public function test_it_rejects_a_range_filter_on_a_text_spec(): void
    {
        Repeater::fake();

        $this->actingAsAdmin();

        $category = Category::factory()->create();

        Livewire::test(CreateType::class)
            ->fillForm([
                'name' => 'Laptops',
                'category_id' => $category->id,
                'specDefinitions' => [
                    [
                        'name_en' => 'Colour',
                        'name_ar' => 'اللون',
                        'value_type' => SpecValueType::TEXT->value,
                        'is_filterable' => true,
                        'filter_type' => SpecFilterType::RANGE->value,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['specDefinitions.0.filter_type'])
            ->assertNotNotified();

        $this->assertDatabaseCount('type_specs', 0);
    }

    public function test_it_edits_a_type(): void
    {
        $this->actingAsAdmin();

        $type = Type::factory()->create();

        Livewire::test(EditType::class, [
            'record' => $type->getRouteKey(),
        ])
            ->fillForm([
                'name' => 'Updated Type Name',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('types', [
            'id' => $type->id,
            'name' => 'Updated Type Name',
        ]);
    }

    public function test_it_deletes_a_type(): void
    {
        $this->actingAsAdmin();

        $type = Type::factory()->create();

        Livewire::test(ListTypes::class)
            ->callAction(TestAction::make('delete')->table($type))
            ->assertNotified();

        $this->assertDatabaseMissing('types', [
            'id' => $type->id,
        ]);
    }

    private function createTypeWithSpec(Category $category, string $nameEn): Type
    {
        $type = Type::factory()->create(['category_id' => $category->id]);

        TypeSpec::factory()->for($type)->create([
            'name_en' => $nameEn,
            'name_ar' => $nameEn,
        ]);

        return $type;
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    }
}
