<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Enums\SpecValueType;
use App\Models\Brand;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Type;
use App\Models\TypeSpec;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        $name = fake()->unique()->sentence();
        $price = fake()->randomNumber(3, true);

        $type = Type::query()->inRandomOrder()->first();

        if (! $type) {
            $type = Type::factory()->create();
        }

        /** @var Type $type */
        $specs = ['en' => [], 'ar' => []];

        foreach ($type->specDefinitions as $spec) {
            $specs['en'][$spec->name_en] = $this->specValue($spec, 'en');
            $specs['ar'][$spec->name_ar] = $this->specValue($spec, 'ar');
        }

        $brand = Brand::query()->inRandomOrder(0)->first();

        if (! $brand) {
            $brand = Brand::factory()->create();
        }

        /** @var Brand $brand */
        return [
            'slug' => Str::slug($name),

            'name' => Str::title($name),

            'sku' => Str::slug($brand->name.$name),

            'description_en' => fake()->paragraph(),
            'description_ar' => fake()->paragraph(),

            'weight' => fake()->optional()->numberBetween(0, 200),

            'stock' => fake()->optional(0.5, 0)->numberBetween(0, 500),

            'price_in_fils' => $price,
            'cost_price_in_fils' => fake()->numberBetween(1, $price - 10),

            'type_id' => $type->id,
            'specs_en' => $specs['en'],
            'specs_ar' => $specs['ar'],

            'brand_id' => $brand->id,

            'featured_at' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'activated_at' => fake()->optional()->dateTimeBetween('now', '+30 days'),
        ];
    }

    /**
     * A faked value shaped to match the spec's declared value type.
     */
    private function specValue(TypeSpec $spec, string $locale): string|int|float|bool
    {
        return match ($spec->value_type) {
            SpecValueType::NUMBER => fake()->numberBetween(1, 128),
            SpecValueType::BOOLEAN => fake()->boolean(),
            SpecValueType::OPTIONS => $spec->optionLabels($locale)[0] ?? fake()->word(),
            default => fake()->word(),
        };
    }

    /**
     * Indicate that the product is active.
     */
    public function active(): static
    {
        return $this->state(
            fn (): array => [
                'activated_at' => now()->subDay(),
            ],
        );
    }

    /**
     * Indicate that the product is featured.
     */
    public function featured(): static
    {
        return $this->state(
            fn (): array => [
                'featured_at' => now()->subDay(),
            ],
        );
    }

    /**
     * Indicate that the product is low on stock.
     */
    public function lowStock(): static
    {
        return $this->state(
            fn (): array => [
                'low_stock_threshold' => 5,
                'stock' => fake()->numberBetween(1, 5),
                'reserved_stock' => 0,
            ],
        );
    }

    /**
     * Set explicit localized spec values for the product.
     */
    public function withSpecs(array $en, ?array $ar = null): static
    {
        return $this->state(
            fn (): array => [
                'specs_en' => $en,
                'specs_ar' => $ar ?? $en,
            ],
        );
    }

    /**
     * Attach an active discount to the product.
     */
    public function discounted(
        float $value = 10,
        ?DiscountType $type = null,
    ): static {
        return $this->afterCreating(function (Product $product) use ($value, $type) {
            Discount::factory()
                ->create([
                    'type' => $type ?? DiscountType::PERCENTAGE,
                    'value' => $value,
                ])
                ->products()
                ->attach($product);
        });
    }

    public function withGalleryImages(int $count = 3): static
    {
        return $this->afterCreating(function (Product $product) use ($count) {
            foreach (range(1, $count) as $index) {
                $product->addMedia(
                    UploadedFile::fake()->image(
                        "gallery-{$index}.png",
                        1920,
                        1080,
                    ),
                )
                    ->toMediaCollection('gallery');
            }
        });
    }

    public function withThumbnailImage(): static
    {
        return $this->afterCreating(function (Product $product) {
            $product->addMedia(
                UploadedFile::fake()->image(
                    'thumb.png',
                    1920,
                    1080,
                ),
            )
                ->toMediaCollection('thumbnail');
        });
    }
}
