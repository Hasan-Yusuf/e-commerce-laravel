<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SpecFilterType;
use App\Enums\SpecValueType;
use App\Models\Category;
use App\Models\Type;
use App\Models\TypeSpec;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Type>
 */
class TypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        $categories = Category::query()
            ->inRandomOrder()
            ->first()
            ?->pluck('id')
            ?->toArray();

        if (! $categories) {
            $categories = Category::factory(3)->create();
            $categories = $categories->pluck('id')->toArray();
        }

        return [
            'category_id' => fake()->randomElement($categories),

            'name' => fake()->word(),
        ];
    }

    /**
     * Give each type a small, predictable spec schema so downstream factories
     * and tests can exercise every value type without setting one up by hand.
     */
    #[\Override]
    public function configure(): static
    {
        return $this->afterCreating(function (Type $type): void {
            if ($type->specDefinitions()->exists()) {
                return;
            }

            TypeSpec::factory()
                ->count(4)
                ->for($type)
                ->sequence(
                    [
                        'name_en' => 'Memory',
                        'name_ar' => 'الذاكرة',
                        'value_type' => SpecValueType::NUMBER,
                        'unit' => 'GB',
                        'is_filterable' => true,
                        'filter_type' => SpecFilterType::RANGE,
                        'sort_order' => 0,
                    ],
                    [
                        'name_en' => 'Colour',
                        'name_ar' => 'اللون',
                        'value_type' => SpecValueType::OPTIONS,
                        'options' => [
                            ['label_en' => 'Black', 'label_ar' => 'أسود'],
                            ['label_en' => 'Silver', 'label_ar' => 'فضي'],
                        ],
                        'is_filterable' => true,
                        'filter_type' => SpecFilterType::LIST,
                        'sort_order' => 1,
                    ],
                    [
                        'name_en' => 'Warranty',
                        'name_ar' => 'الضمان',
                        'value_type' => SpecValueType::TEXT,
                        'sort_order' => 2,
                    ],
                    [
                        'name_en' => 'RGB',
                        'name_ar' => 'إضاءة RGB',
                        'value_type' => SpecValueType::BOOLEAN,
                        'sort_order' => 3,
                    ],
                )
                ->create();
        });
    }
}
