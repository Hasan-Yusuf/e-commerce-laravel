<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SpecValueType;
use App\Models\Type;
use App\Models\TypeSpec;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TypeSpec>
 */
class TypeSpecFactory extends Factory
{
    protected $model = TypeSpec::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'type_id' => Type::factory(),

            'name_en' => fake()->unique()->words(2, true),
            'name_ar' => fake()->unique()->words(2, true),

            'value_type' => SpecValueType::TEXT,
            'options' => null,
            'unit' => null,

            'is_filterable' => false,
            'filter_type' => null,

            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the spec holds a numeric value.
     */
    public function number(?string $unit = null): static
    {
        return $this->state(fn (): array => [
            'value_type' => SpecValueType::NUMBER,
            'unit' => $unit,
        ]);
    }

    /**
     * Indicate that the spec holds one of a fixed set of labels.
     *
     * @param  array<int, array{label_en: string, label_ar: string}>  $options
     */
    public function options(array $options): static
    {
        return $this->state(fn (): array => [
            'value_type' => SpecValueType::OPTIONS,
            'options' => $options,
        ]);
    }

    /**
     * Indicate that the spec holds a yes/no value.
     */
    public function boolean(): static
    {
        return $this->state(fn (): array => [
            'value_type' => SpecValueType::BOOLEAN,
        ]);
    }
}
