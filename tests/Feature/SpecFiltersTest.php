<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SpecFilterType;
use App\Enums\SpecValueType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use App\Models\TypeSpec;
use App\Services\Catalog\ProductQueryService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The storefront spec filters: the values the shopper picks are matched against
 * the typed specs the admin marked filterable, on the category page and the
 * products index alike.
 */
class SpecFiltersTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_list_spec_filter_matches_products_by_their_value(): void
    {
        [$category, $type, , $colour] = $this->catalog();

        $this->product($type, 'Black Product', ['Memory' => 16, 'Colour' => 'Black']);
        $this->product($type, 'Silver Product', ['Memory' => 16, 'Colour' => 'Silver']);

        static::assertSame(
            ['Black Product'],
            $this->matchingNames($category, [
                'specs' => [$colour->id => ['Black']],
            ]),
        );
    }

    public function test_a_list_spec_filter_matches_every_selected_value(): void
    {
        [$category, $type, , $colour] = $this->catalog();

        $this->product($type, 'Black Product', ['Memory' => 16, 'Colour' => 'Black']);
        $this->product($type, 'Silver Product', ['Memory' => 16, 'Colour' => 'Silver']);
        $this->product($type, 'White Product', ['Memory' => 16, 'Colour' => 'White']);

        static::assertSame(
            ['Black Product', 'Silver Product'],
            $this->matchingNames($category, [
                'specs' => [$colour->id => ['Black', 'Silver']],
            ]),
        );
    }

    public function test_a_text_list_spec_matches_by_its_stored_value(): void
    {
        $category = $this->activeCategory();

        $type = $this->typeWithSpecs($category, [
            [
                'name' => 'Panel Type',
                'value_type' => SpecValueType::TEXT,
                'filter_type' => SpecFilterType::LIST,
            ],
        ]);

        $panel = $type->specDefinitions->firstWhere('name_en', 'Panel Type');

        $this->product($type, 'IPS Panel', ['Panel Type' => 'IPS']);
        $this->product($type, 'VA Panel', ['Panel Type' => 'VA']);

        static::assertSame(
            ['IPS Panel'],
            $this->matchingNames($category, [
                'specs' => [$panel->id => ['IPS']],
            ]),
        );
    }

    public function test_a_boolean_list_spec_matches_yes_and_no(): void
    {
        $category = $this->activeCategory();

        $type = $this->typeWithSpecs($category, [
            [
                'name' => 'RGB',
                'value_type' => SpecValueType::BOOLEAN,
                'filter_type' => SpecFilterType::LIST,
            ],
        ]);

        $rgb = $type->specDefinitions->firstWhere('name_en', 'RGB');

        $this->product($type, 'Lit Product', ['RGB' => true]);
        $this->product($type, 'Plain Product', ['RGB' => false]);

        static::assertSame(
            ['Lit Product'],
            $this->matchingNames($category, ['specs' => [$rgb->id => ['1']]]),
        );

        static::assertSame(
            ['Plain Product'],
            $this->matchingNames($category, ['specs' => [$rgb->id => ['0']]]),
        );

        // A yes/no is read as a boolean however it is spelled: the word the
        // dropdown posts, a real boolean, or the spelling SQLite keeps.
        static::assertSame(
            ['Lit Product'],
            $this->matchingNames($category, ['specs' => [$rgb->id => [true]]]),
        );

        static::assertSame(
            ['Plain Product'],
            $this->matchingNames($category, ['specs' => [$rgb->id => [false]]]),
        );
    }

    public function test_a_range_spec_filter_narrows_by_numeric_bounds(): void
    {
        [$category, $type, $memory] = $this->catalog();

        $this->product($type, 'Eight GB Product', ['Memory' => 8]);
        $this->product($type, 'Sixteen GB Product', ['Memory' => 16]);
        $this->product($type, 'Thirty Two GB Product', ['Memory' => 32]);

        // A minimum on its own excludes the smaller value...
        static::assertSame(
            ['Sixteen GB Product', 'Thirty Two GB Product'],
            $this->matchingNames($category, [
                'specs' => [$memory->id => ['min' => 16]],
            ]),
        );

        // ...and so does a maximum.
        static::assertSame(
            ['Eight GB Product', 'Sixteen GB Product'],
            $this->matchingNames($category, [
                'specs' => [$memory->id => ['max' => 16]],
            ]),
        );

        // The bounds compare as numbers: "8" is below a minimum of 9 even
        // though it sorts after "16" as text.
        static::assertSame(
            ['Sixteen GB Product'],
            $this->matchingNames($category, [
                'specs' => [$memory->id => ['min' => 9, 'max' => 20]],
            ]),
        );
    }

    public function test_the_spec_filter_ignores_specs_that_are_not_filterable(): void
    {
        [$category, $type, , , $warranty, $rgb] = $this->catalog();

        $this->product($type, 'Warranty Product', [
            'Memory' => 16,
            'Warranty' => '3 years',
            'RGB' => true,
        ]);
        $this->product($type, 'Other Product', ['Memory' => 8]);

        static::assertSame(
            ['Other Product', 'Warranty Product'],
            $this->matchingNames($category, [
                'specs' => [
                    $warranty->id => ['3 years'],
                    $rgb->id => ['1'],
                ],
            ]),
        );
    }

    public function test_the_spec_filter_ignores_unknown_ids_and_empty_selections(): void
    {
        [$category, $type, $memory] = $this->catalog();

        $this->product($type, 'Untouched Product', ['Memory' => 16]);

        static::assertSame(
            ['Untouched Product'],
            $this->matchingNames($category, []),
        );

        static::assertSame(
            ['Untouched Product'],
            $this->matchingNames($category, ['specs' => []]),
        );

        static::assertSame(
            ['Untouched Product'],
            $this->matchingNames($category, [
                'specs' => [
                    'not-an-id' => ['x'],
                    999_999 => ['x'],
                    $memory->id => ['min' => null, 'max' => null],
                ],
            ]),
        );
    }

    public function test_spec_options_cover_the_filterable_specs_of_the_category_once(): void
    {
        $category = $this->activeCategory();

        // Two types declare the same specs; the storefront shows them once.
        $this->catalog($category);
        $this->catalog($category);

        $other = $this->activeCategory();
        $this->typeWithSpecs($other, [
            [
                'name' => 'Storage',
                'value_type' => SpecValueType::NUMBER,
                'unit' => 'GB',
                'filter_type' => SpecFilterType::RANGE,
            ],
        ]);

        $query = app(ProductQueryService::class);

        static::assertSame(
            ['Memory', 'Colour'],
            $query->specOptions($category)->pluck('name')->all(),
        );

        static::assertSame(
            ['Storage'],
            $query->specOptions($other)->pluck('name')->all(),
        );
    }

    public function test_spec_options_are_resolved_in_one_batched_query(): void
    {
        $category = $this->activeCategory();

        $type = $this->typeWithSpecs($category, [
            ['name' => 'Colour', 'value_type' => SpecValueType::TEXT, 'filter_type' => SpecFilterType::LIST],
            ['name' => 'Panel', 'value_type' => SpecValueType::TEXT, 'filter_type' => SpecFilterType::LIST],
            ['name' => 'Warranty', 'value_type' => SpecValueType::TEXT, 'filter_type' => SpecFilterType::LIST],
            ['name' => 'Connectivity', 'value_type' => SpecValueType::TEXT, 'filter_type' => SpecFilterType::LIST],
        ]);

        $this->product($type, 'Four Spec Product', [
            'Colour' => 'Black',
            'Panel' => 'IPS',
            'Warranty' => '2',
            'Connectivity' => 'Wireless',
        ]);

        DB::enableQueryLog();
        DB::flushQueryLog();

        app(ProductQueryService::class)->specOptions($category);

        $queries = collect(DB::getQueryLog())->pluck('query');

        DB::disableQueryLog();

        // One query however many filterable specs there are; a per-spec
        // resolution would run four.
        static::assertCount(
            1,
            $queries->filter(
                static fn (string $query): bool => str_contains($query, 'spec_value'),
            ),
        );
    }

    public function test_spec_options_are_cached_per_category_scope(): void
    {
        $category = $this->activeCategory();
        $this->catalog($category);

        $query = app(ProductQueryService::class);
        $query->specOptions($category);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $query->specOptions($category);

        $queries = collect(DB::getQueryLog())->pluck('query');

        DB::disableQueryLog();

        static::assertCount(
            0,
            $queries->filter(
                static fn (string $query): bool => str_contains($query, 'spec_value'),
            ),
            'A second read of the same scope should come from the cache.',
        );
    }

    public function test_spec_options_list_the_values_the_products_carry(): void
    {
        $category = $this->activeCategory();

        $type = $this->typeWithSpecs($category, [
            [
                'name' => 'Lighting',
                'value_type' => SpecValueType::BOOLEAN,
                'filter_type' => SpecFilterType::LIST,
            ],
            [
                'name' => 'Panel Type',
                'value_type' => SpecValueType::TEXT,
                'filter_type' => SpecFilterType::LIST,
            ],
        ]);

        $this->product($type, 'Lit IPS Panel', [
            'Lighting' => true,
            'Panel Type' => 'IPS',
        ]);
        $this->product($type, 'Plain VA Panel', [
            'Lighting' => false,
            'Panel Type' => 'VA',
        ]);
        $this->product($type, 'Other IPS Panel', [
            'Lighting' => true,
            'Panel Type' => 'IPS',
        ]);

        $specs = app(ProductQueryService::class)->specOptions($category)->keyBy('name');

        // A yes/no spec offers both answers, labelled for a shopper.
        static::assertSame(
            ['0' => 'No', '1' => 'Yes'],
            $specs['Lighting']->values,
        );

        // A text spec offers each distinct value once, in order.
        static::assertSame(
            ['IPS' => 'IPS', 'VA' => 'VA'],
            $specs['Panel Type']->values,
        );
    }

    public function test_a_number_spec_offers_the_values_it_declares(): void
    {
        $category = $this->activeCategory();

        // A memory spec whose values are fixed, the way RAM sizes are.
        $type = $this->typeWithSpecs($category, [
            [
                'name' => 'Memory',
                'value_type' => SpecValueType::NUMBER,
                'unit' => 'GB',
                'filter_type' => SpecFilterType::LIST,
                'options' => [
                    ['label_en' => '8', 'label_ar' => '8'],
                    ['label_en' => '16', 'label_ar' => '16'],
                    ['label_en' => '32', 'label_ar' => '32'],
                ],
            ],
        ]);

        $memory = $type->specDefinitions->firstWhere('name_en', 'Memory');

        $this->product($type, 'Sixteen GB Product', ['Memory' => 16]);

        // Only the 16 GB machine exists, but the spec names the other sizes, so
        // the shopper can ask for them — in numeric order, not alphabetical.
        $specs = app(ProductQueryService::class)->specOptions($category)->keyBy('name');

        static::assertSame(
            ['8' => '8', '16' => '16', '32' => '32'],
            $specs['Memory']->values,
        );

        // And picking one of the declared values narrows the listing.
        static::assertSame(
            ['Sixteen GB Product'],
            $this->matchingNames($category, [
                'specs' => [$memory->id => ['16']],
            ]),
        );
    }

    public function test_a_list_filter_is_a_dropdown_of_checkboxes(): void
    {
        [$category, $type, , $colour] = $this->catalog();

        $this->product($type, 'Black Product', ['Memory' => 16, 'Colour' => 'Black']);
        $this->product($type, 'Silver Product', ['Memory' => 16, 'Colour' => 'Silver']);

        $html = $this
            ->get(route('category.show', [
                $category->slug,
                'specs' => [$colour->id => ['Black']],
            ]))
            ->assertOk()
            ->getContent();

        // The filter's title is the label over its controls; the dropdown it
        // names runs to its own closing tag.
        $dropdown = Str::between($html, '>Colour</p>', '</details>');

        // The values live behind a dropdown, so however many the catalogue has
        // the sidebar stays one control tall...
        static::assertStringContainsString('class="filter-dropdown"', $dropdown);
        // ...and the word it falls back to when nothing is chosen rides on it,
        // for the script that keeps the summary in step.
        static::assertStringContainsString(
            'data-empty-label="'.__('catalog.filters.choose').'"',
            $dropdown,
        );

        // ...and what it holds is still a checkbox per value — several can be
        // picked at once, the applied one comes back checked, and the closed
        // summary reads it back.
        static::assertStringContainsString('name="specs['.$colour->id.'][]"', $dropdown);
        static::assertStringContainsString('value="Black"', $dropdown);
        static::assertStringContainsString('value="Silver"', $dropdown);
        static::assertStringContainsString('checked', $dropdown);
        static::assertStringContainsString(
            'Black',
            Str::before(Str::after($dropdown, 'filter-dropdown__value">'), '</span>'),
        );
    }

    public function test_a_yes_no_filter_is_a_dropdown_of_yes_no_and_not_asked(): void
    {
        $category = $this->activeCategory();

        $type = $this->typeWithSpecs($category, [
            [
                'name' => 'Lighting',
                'value_type' => SpecValueType::BOOLEAN,
                'filter_type' => SpecFilterType::LIST,
            ],
        ]);

        $lighting = $type->specDefinitions->firstWhere('name_en', 'Lighting');

        $this->product($type, 'Lit Product', ['Lighting' => true]);
        $this->product($type, 'Plain Product', ['Lighting' => false]);

        $html = $this
            ->get(route('category.show', [$category->slug]))
            ->assertOk()
            ->getContent();

        $field = 'name="specs['.$lighting->id.']"';
        $select = Str::between($html, '<select class="filter-select"', '</select>');

        // One line, three answers, the spec's own name over them — rather than a
        // heading and a box for each answer, which is the room the dropdown
        // saves.
        static::assertSame(1, substr_count($html, $field));
        static::assertStringContainsString('id="spec-'.$lighting->id.'"', $select);
        static::assertStringContainsString($field, $select);
        static::assertStringContainsString('value="1"', $select);
        static::assertStringContainsString(__('catalog.product.spec_yes'), $select);
        static::assertStringContainsString('value="0"', $select);
        static::assertStringContainsString(__('catalog.product.spec_no'), $select);
        static::assertStringContainsString(__('catalog.filters.choose'), $select);

        // Not asked leaves the listing as it was: an empty answer narrows
        // nothing rather than asking for "no".
        $this
            ->get(route('category.show', [
                $category->slug,
                'specs' => [$lighting->id => ''],
            ]))
            ->assertOk()
            ->assertSee('Lit Product')
            ->assertSee('Plain Product');
    }

    public function test_a_filter_summary_names_a_few_choices_and_counts_the_rest(): void
    {
        $category = $this->activeCategory();
        $type = $this->catalog($category)[1];

        $brands = [];

        // Four brands, each with a product of its own, so all four are offered.
        foreach (['Alpha', 'Beta', 'Gamma', 'Delta'] as $name) {
            $brand = Brand::factory()->create(['name_en' => $name]);
            $brands[] = $brand->id;

            Product::factory()
                ->active()
                ->create([
                    'name' => "{$name} Product",
                    'type_id' => $type->id,
                    'brand_id' => $brand->id,
                ]);
        }

        $html = $this
            ->get(route('category.show', [
                $category->slug,
                'brand' => $brands,
            ]))
            ->assertOk()
            ->getContent();

        $summary = Str::before(
            Str::after($html, 'filter-dropdown__value">'),
            '</span>',
        );

        // Three names, then what is left is counted: the closed line stays a
        // line rather than growing with the selection.
        static::assertSame(2, substr_count($summary, ','));
        static::assertStringContainsString(
            __('catalog.filters.more', ['count' => 1]),
            $summary,
        );
    }

    public function test_the_category_page_renders_a_list_and_a_range_control(): void
    {
        [$category, $type, $memory, $colour] = $this->catalog();

        $this->product($type, 'Black Rendered Product', [
            'Memory' => 16,
            'Colour' => 'Black',
        ]);
        $this->product($type, 'Silver Rendered Product', [
            'Memory' => 32,
            'Colour' => 'Silver',
        ]);

        $this
            ->get(route('category.show', $category->slug))
            ->assertOk()
            // A range spec is a min/max pair, titled with the unit it measures.
            ->assertSee('Memory (GB)')
            ->assertSee('name="specs['.$memory->id.'][min]"', false)
            ->assertSee('name="specs['.$memory->id.'][max]"', false)
            // A list spec is a checkbox per value the products carry.
            ->assertSee('name="specs['.$colour->id.'][]"', false)
            ->assertSee('value="Black"', false)
            ->assertSee('value="Silver"', false);
    }

    public function test_the_storefront_leaves_non_filterable_specs_out(): void
    {
        [$category, $type, , , $warranty, $rgb] = $this->catalog();

        $this->product($type, 'Quiet Product', [
            'Memory' => 16,
            'Colour' => 'Black',
            'Warranty' => '3 years',
            'RGB' => true,
        ]);

        $this
            ->get(route('category.show', $category->slug))
            ->assertOk()
            ->assertDontSee('name="specs['.$warranty->id.']', false)
            ->assertDontSee('name="specs['.$rgb->id.']', false);

        $this
            ->get(route('products.index'))
            ->assertOk()
            ->assertDontSee('name="specs['.$warranty->id.']', false)
            ->assertDontSee('name="specs['.$rgb->id.']', false);
    }

    public function test_the_products_page_filters_by_a_spec(): void
    {
        [$category, $type, $memory, $colour] = $this->catalog();

        $this->product($type, 'Black Sixteen', ['Memory' => 16, 'Colour' => 'Black']);
        $this->product($type, 'Black Eight', ['Memory' => 8, 'Colour' => 'Black']);
        $this->product($type, 'Silver Sixteen', ['Memory' => 16, 'Colour' => 'Silver']);

        $this
            ->get(route('products.index', [
                'specs' => [
                    $memory->id => ['min' => 16],
                    $colour->id => ['Black'],
                ],
            ]))
            ->assertOk()
            ->assertSee('Black Sixteen')
            ->assertDontSee('Black Eight')
            ->assertDontSee('Silver Sixteen');
    }

    public function test_the_applied_specs_ride_along_in_the_sort_search_and_view_forms(): void
    {
        [$category, $type, $memory, $colour] = $this->catalog();

        $this->product($type, 'Preserved Product', [
            'Memory' => 32,
            'Colour' => 'Black',
        ]);

        $html = $this
            ->get(route('products.index', [
                'q' => 'Preserved',
                'specs' => [
                    $memory->id => ['min' => 16],
                    $colour->id => ['Black'],
                ],
            ]))
            ->assertOk()
            ->getContent();

        $range = 'name="specs['.$memory->id.'][min]"';
        $list = 'name="specs['.$colour->id.'][]"';

        // The controls live in the sidebar's own form; every other form on the
        // page carries the selection as hidden fields.
        foreach (['catalog-sort', 'catalog-view', 'search-toolbar'] as $form) {
            $fields = Str::between($html, $form, '</form>');

            static::assertStringContainsString(
                $range,
                $fields,
                "The [{$form}] form drops the applied range spec.",
            );
            static::assertStringContainsString(
                $list,
                $fields,
                "The [{$form}] form drops the applied list spec.",
            );
        }
    }

    public function test_pagination_keeps_the_applied_specs(): void
    {
        [$category, $type, $memory] = $this->catalog();

        foreach (range(1, 13) as $index) {
            $this->product($type, "Paged Product {$index}", ['Memory' => 32]);
        }

        $this
            ->get(route('products.index', [
                'specs' => [$memory->id => ['min' => 16]],
            ]))
            ->assertOk()
            ->assertSee('specs%5B'.$memory->id.'%5D%5Bmin%5D=16', false);
    }

    public function test_spec_filters_render_removable_chips(): void
    {
        [$category, $type, $memory, $colour] = $this->catalog();

        $this->product($type, 'Chipped Product', [
            'Memory' => 16,
            'Colour' => 'Black',
        ]);

        $this
            ->get(route('products.index', [
                'specs' => [
                    $memory->id => ['min' => 16, 'max' => 32],
                    $colour->id => ['Black'],
                ],
            ]))
            ->assertOk()
            ->assertSee('Memory: 16 GB – 32 GB')
            ->assertSee('Colour: Black')
            // Each chip removes only its own spec.
            ->assertSee(
                e(route('products.index', ['specs' => [$colour->id => ['Black']]])),
                false,
            )
            ->assertSee(
                e(route('products.index', [
                    'specs' => [$memory->id => ['min' => 16, 'max' => 32]],
                ])),
                false,
            );
    }

    public function test_spec_filters_read_and_filter_in_arabic(): void
    {
        [$category, $type] = $this->catalog();

        $this->product($type, 'Arabic Product', ['Memory' => 16, 'Colour' => 'Black']);
        $this->product($type, 'Silver Product', ['Memory' => 8, 'Colour' => 'Silver']);

        $colour = $type->specDefinitions->firstWhere('name_en', 'Colour');

        $this
            ->withSession(['locale' => 'ar'])
            ->get(route('products.index', [
                'specs' => [$colour->id => ['أسود']],
            ]))
            ->assertOk()
            // The spec is rendered in Arabic, with the values it holds.
            ->assertSee('اللون')
            ->assertSee('أسود')
            // And the value narrows the listing by the Arabic column.
            ->assertSee('Arabic Product')
            ->assertDontSee('Silver Product');
    }

    /**
     * A category holding one type with the factory's spec schema: a filterable
     * number spec (Memory, a range in GB), a filterable options spec (Colour, a
     * list), plus two the shopper must not be able to filter by.
     *
     * @return array{0: Category, 1: Type, 2: TypeSpec, 3: TypeSpec, 4: TypeSpec, 5: TypeSpec}
     */
    private function catalog(?Category $category = null): array
    {
        $category ??= $this->activeCategory();

        $type = Type::factory()->create(['category_id' => $category->id]);

        $specs = $type->specDefinitions;

        return [
            $category,
            $type,
            $specs->firstWhere('name_en', 'Memory'),
            $specs->firstWhere('name_en', 'Colour'),
            $specs->firstWhere('name_en', 'Warranty'),
            $specs->firstWhere('name_en', 'RGB'),
        ];
    }

    private function activeCategory(): Category
    {
        return Category::factory()->active()->create(['parent_id' => null]);
    }

    /**
     * A type whose spec schema is exactly the given rows.
     *
     * @param  array<int, array{name: string, value_type: SpecValueType, unit?: string, filter_type?: SpecFilterType, options?: array<int, array{label_en: string, label_ar: string}>}>  $specs
     */
    private function typeWithSpecs(Category $category, array $specs): Type
    {
        $type = Type::factory()->create(['category_id' => $category->id]);

        // Drop the schema the factory seeded and declare this one instead.
        $type->specDefinitions()->delete();

        foreach (array_values($specs) as $index => $spec) {
            $type->specDefinitions()->create([
                'name_en' => $spec['name'],
                'name_ar' => $spec['name'],
                'value_type' => $spec['value_type'],
                'unit' => $spec['unit'] ?? null,
                'options' => $spec['options'] ?? null,
                'is_filterable' => ($spec['filter_type'] ?? null) !== null,
                'filter_type' => $spec['filter_type'] ?? null,
                'sort_order' => $index,
            ]);
        }

        return $type->unsetRelation('specDefinitions');
    }

    /**
     * A product of the type carrying the given English spec values, with the
     * Arabic column keyed by the same specs' Arabic names.
     *
     * @param  array<string, string|int|bool>  $specsEn
     */
    private function product(Type $type, string $name, array $specsEn): Product
    {
        $specsAr = [];

        foreach ($type->specDefinitions as $spec) {
            if (! array_key_exists($spec->name_en, $specsEn)) {
                continue;
            }

            $specsAr[$spec->name_ar] = $this->localize($spec, $specsEn[$spec->name_en]);
        }

        return Product::factory()
            ->active()
            ->create([
                'type_id' => $type->id,

                'name' => $name,
                'slug' => Str::slug($name),

                'specs_en' => $specsEn,
                'specs_ar' => $specsAr,
            ]);
    }

    /**
     * An options value keyed in English reads differently in Arabic, so pair it
     * with the option's own Arabic label; everything else reads the same.
     */
    private function localize(TypeSpec $spec, mixed $value): mixed
    {
        if ($spec->value_type->isNot(SpecValueType::OPTIONS)) {
            return $value;
        }

        foreach ($spec->options ?? [] as $option) {
            if (($option['label_en'] ?? null) === $value) {
                return $option['label_ar'] ?? $value;
            }
        }

        return $value;
    }

    /**
     * The names of the products the catalog parameters select, sorted.
     *
     * @param  array<string, mixed>  $params
     * @return array<int, string>
     */
    private function matchingNames(Category $category, array $params): array
    {
        return app(ProductQueryService::class)
            ->query($params, $category)
            ->pluck('name')
            ->sort()
            ->values()
            ->all();
    }
}
