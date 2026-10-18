<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Enums\SpecFilterType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use App\Models\TypeSpec;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The catalogue's filters — the sidebar and the chips — as a component of their
 * own. They say what the listing is narrowed by and announce it; the products
 * container beside them is what picks the announcement up, so these cover the
 * state a choice writes and the announcement it sends.
 */
class CatalogBrowserTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_filter_is_announced_with_the_state_the_products_are_asked_for(): void
    {
        [$category] = $this->catalog();

        Livewire::test('catalog-browser', ['categoryId' => $category->id])
            ->set('brand', ['2'])
            ->assertDispatched(
                'filters-updated',
                static fn (string $event, array $params): bool => (
                    $params['filters']['brand'] === ['2']
                    && $params['filters']['specs'] === []
                ),
            );
    }

    public function test_the_lone_boxes_ask_their_own_question_and_announce_it(): void
    {
        [$category] = $this->catalog();

        Livewire::test('catalog-browser', ['categoryId' => $category->id])
            ->assertSet('availability', null)
            ->assertSet('discounted', null)
            ->call('toggleAvailability')
            ->assertSet('availability', 'in_stock')
            ->assertDispatched(
                'filters-updated',
                static fn (string $event, array $params): bool => $params['filters']['availability'] === 'in_stock',
            )
            ->call('toggleDiscounted')
            ->assertSet('discounted', '1')
            // Asked again, each lets its filter go.
            ->call('toggleAvailability')
            ->assertSet('availability', null)
            ->call('toggleDiscounted')
            ->assertSet('discounted', null);
    }

    public function test_a_chip_drops_only_its_own_spec_and_announces_it(): void
    {
        [$category, , $memory, $colour] = $this->catalog();

        Livewire::test('catalog-browser', ['categoryId' => $category->id])
            ->set('specs', [
                $memory->id => ['min' => 16],
                $colour->id => ['Black'],
            ])
            ->call('removeFilter', 'specs', $colour->id)
            ->assertSet(
                'specs',
                static fn (array $specs): bool => $specs[$colour->id] === [] && $specs[$memory->id] === ['min' => 16],
            )
            ->assertDispatched(
                'filters-updated',
                static fn (string $event, array $params): bool => $params['filters']['specs'] === [
                    $memory->id => ['min' => 16],
                ],
            );
    }

    public function test_clearing_drops_the_filters_and_announces_it(): void
    {
        [$category, , , $colour] = $this->catalog();

        Livewire::test('catalog-browser', ['categoryId' => $category->id])
            ->set('brand', ['2'])
            ->set('specs', [$colour->id => ['Black']])
            ->set('availability', 'in_stock')
            ->set('price_min', '0.5')
            ->call('clear')
            ->assertSet('brand', [])
            ->assertSet('availability', null)
            ->assertSet('price_min', null)
            ->assertSet('specs', static fn (array $specs): bool => $specs[$colour->id] === [])
            ->assertDispatched(
                'filters-updated',
                static fn (string $event, array $params): bool => (
                    $params['filters']['brand'] === []
                    && $params['filters']['availability'] === null
                    && $params['filters']['specs'] === []
                ),
            );
    }

    public function test_the_spec_choices_arrive_with_a_list_to_bind_to(): void
    {
        [$category, , $memory, $colour] = $this->catalog();

        $warranty = Type::query()
            ->firstOrFail()
            ->specDefinitions()
            ->where('name_en', 'Warranty')
            ->sole();

        // A checkbox group binds a list, so every spec the sidebar offers has to
        // arrive as one — the ones nobody has ticked yet included — while a spec
        // the sidebar leaves out needs no key at all.
        Livewire::test('catalog-browser', ['categoryId' => $category->id])
            ->assertSet(
                'specs',
                static fn (array $specs): bool => (
                    array_key_exists($colour->id, $specs)
                    && array_key_exists($memory->id, $specs)
                    && $specs[$colour->id] === []
                    && $specs[$memory->id] === []
                    && ! array_key_exists($warranty->id, $specs)
                ),
            );
    }

    public function test_a_yes_no_spec_answers_yes_no_or_not_asked(): void
    {
        [$category, $type] = $this->catalog();

        $rgb = $type->specDefinitions->firstWhere('name_en', 'RGB');
        $rgb->update([
            'is_filterable' => true,
            'filter_type' => SpecFilterType::LIST,
        ]);

        Livewire::test('catalog-browser', ['categoryId' => $category->id])
            // The dropdown starts on "not asked", which narrows nothing...
            ->assertSet('specs', static fn (array $specs): bool => $specs[$rgb->id] === '')
            ->set('specs.'.$rgb->id, '1')
            ->assertSet('specs', static fn (array $specs): bool => $specs[$rgb->id] === '1')
            ->assertDispatched(
                'filters-updated',
                static fn (string $event, array $params): bool => $params['filters']['specs'] === [$rgb->id => '1'],
            )
            // ...and so does going back to it, while "no" is an answer of its own.
            ->set('specs.'.$rgb->id, '0')
            ->assertDispatched(
                'filters-updated',
                static fn (string $event, array $params): bool => $params['filters']['specs'] === [$rgb->id => '0'],
            )
            ->set('specs.'.$rgb->id, '')
            ->assertDispatched(
                'filters-updated',
                static fn (string $event, array $params): bool => $params['filters']['specs'] === [],
            );
    }

    public function test_the_sidebar_and_its_chips_answer_the_filters_rather_than_a_posted_form(): void
    {
        [$category, $type] = $this->catalog();

        $product = $this->product($type, 'Listed Product', ['Memory' => 16, 'Colour' => 'Black']);

        $this
            ->get(route('category.show', $category->slug))
            ->assertOk()
            // Every choice answers the filters as it changes...
            ->assertSee('wire:model.live="brand"', false)
            ->assertSee('wire:model.live.debounce.500ms="price_min"', false)
            ->assertSee('wire:click="toggleAvailability"', false)
            ->assertSee('wire:click="toggleDiscounted"', false)
            // ...so the sidebar's submit is left to a browser without the script.
            ->assertSee('filter-form__apply no-js-only', false);

        // And a chip takes its filter back the same way its link does.
        $this
            ->get(route('category.show', [
                'category' => $category->slug,
                'brand' => [$product->brand_id],
            ]))
            ->assertOk()
            ->assertSee("wire:click.prevent=\"removeFilter('brand', null)\"", false);
    }

    /**
     * A category holding one type with the factory's spec schema: a filterable
     * number spec (Memory, a range in GB) and a filterable options spec (Colour).
     *
     * @return array{0: Category, 1: Type, 2: TypeSpec, 3: TypeSpec}
     */
    private function catalog(?Category $category = null): array
    {
        $category ??= Category::factory()->active()->create(['parent_id' => null]);

        $type = Type::factory()->create(['category_id' => $category->id]);

        return [
            $category,
            $type,
            $type->specDefinitions->firstWhere('name_en', 'Memory'),
            $type->specDefinitions->firstWhere('name_en', 'Colour'),
        ];
    }

    /**
     * A product of the type carrying the given English spec values.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function product(Type $type, string $name, array $attributes): Product
    {
        $specs = collect($attributes)->filter(static fn (mixed $value, string $key): bool => $type->specDefinitions
            ->contains('name_en', $key))
            ->all();

        return Product::factory()
            ->active()
            ->for($type)
            ->withSpecs($specs)
            ->create(array_merge(
                [
                    'name' => $name,
                    'stock' => 5,
                    'reserved_stock' => 0,
                    'low_stock_threshold' => 0,
                ],
                array_diff_key($attributes, $specs),
            ));
    }
}
