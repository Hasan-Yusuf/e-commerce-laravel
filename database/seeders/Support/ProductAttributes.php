<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Models\Brand;
use App\Models\Type;
use RuntimeException;

class ProductAttributes
{
    /**
     * Marketing copy templates per category, picked in turn so the catalogue
     * reads naturally instead of repeating one sentence.
     *
     * @var array<string, array{en: array<int, string>, ar: array<int, string>}>
     */
    private const DESCRIPTIONS = [
        'electronics' => [
            'en' => [
                'The :name pairs dependable performance with a clean, modern design.',
                'An everyday :name built to last, with the features that matter most.',
            ],
            'ar' => [
                'يجمع :name بين الأداء الموثوق والتصميم العصري الأنيق.',
                ':name جهاز يومي مصمم ليدوم، بالميزات الأكثر أهمية.',
            ],
        ],
        'home-kitchen' => [
            'en' => [
                'The :name makes everyday tasks easier and keeps your home running smoothly.',
                'A practical :name that blends into any kitchen or living space.',
            ],
            'ar' => [
                'يجعل :name المهام اليومية أسهل ويحافظ على سير منزلك بسلاسة.',
                ':name عملي يندمج بسهولة في أي مطبخ أو مساحة معيشة.',
            ],
        ],
        'fashion' => [
            'en' => [
                'The :name combines comfort with a style that fits any wardrobe.',
                'A versatile :name made from materials chosen for everyday wear.',
            ],
            'ar' => [
                'يجمع :name بين الراحة والأناقة التي تناسب أي خزانة ملابس.',
                ':name متعدد الاستخدامات مصنوع من خامات مختارة للارتداء اليومي.',
            ],
        ],
        'sports-outdoors' => [
            'en' => [
                'The :name is built to keep up with an active lifestyle.',
                'A dependable :name for training, travel and time outside.',
            ],
            'ar' => [
                'صُمم :name لمواكبة نمط الحياة النشط.',
                ':name موثوق للتدريب والسفر وقضاء الوقت في الخارج.',
            ],
        ],
        'books' => [
            'en' => [
                'The :name is a rewarding read for anyone who picks it up.',
                'A thoughtfully produced :name that belongs on every shelf.',
            ],
            'ar' => [
                ':name قراءة ممتعة لكل من يقتنيه.',
                ':name منتَج بعناية يستحق مكاناً في كل رف.',
            ],
        ],
        'beauty' => [
            'en' => [
                'The :name is formulated to fit easily into your routine.',
                'A gentle, everyday :name made for all skin types.',
            ],
            'ar' => [
                'صُمم :name ليناسب روتينك بسهولة.',
                ':name لطيف للاستخدام اليومي ومناسب لجميع أنواع البشرة.',
            ],
        ],
    ];

    /**
     * Realistic shipping-weight range (kg) per category.
     *
     * @var array<string, array{0: float, 1: float}>
     */
    private const WEIGHT_RANGES = [
        'electronics' => [0.05, 2.0],
        'home-kitchen' => [0.2, 12.0],
        'fashion' => [0.1, 2.5],
        'sports-outdoors' => [0.3, 15.0],
        'books' => [0.2, 2.0],
        'beauty' => [0.05, 1.5],
    ];

    /**
     * @param  array{name: string, brand: string, price: float, specs: array<string, string|int|float|bool>, featured?: bool}  $product
     * @param  array{category: string, name: string, specs: array<string, string>}  $definition
     * @param  array{index: int, sequence: int}  $position
     * @return array<string, mixed>
     */
    public function build(
        array $product,
        array $definition,
        Type $type,
        Brand $brand,
        array $position,
    ): array {
        [$specsEn, $specsAr] = $this->localizedSpecs(
            $product['specs'],
            $type,
        );

        $weight = self::WEIGHT_RANGES[$definition['category']] ?? [0.1, 5.0];

        $stock = fake()->boolean(85)
            ? fake()->numberBetween(5, 45)
            : fake()->numberBetween(0, 4);

        $priceInFils = (int) round($product['price'] * 1000);

        return [
            'slug' => (string) str($product['name'])->slug(),

            'name' => $product['name'],
            'meta_name' => $product['name'].' | Nexus',

            'sku' => 'NEX-'
                    .strtoupper(substr($brand->name_en, 0, 3))
                    .'-'
                    .str_pad((string) $position['sequence'], 4, '0', STR_PAD_LEFT),

            'description_en' => $this->description(
                $definition['category'],
                'en',
                $position['index'],
                $product['name'],
            ),
            'description_ar' => $this->description(
                $definition['category'],
                'ar',
                $position['index'],
                $product['name'],
            ),
            'meta_description' => 'Buy the '
                    .$product['name']
                    .' from Nexus. Genuine '
                    .$brand->name_en
                    .' stock, official warranty and fast delivery.',

            'weight' => fake()->randomFloat(2, $weight[0], $weight[1]),

            'stock' => $stock,
            'reserved_stock' => $stock > 0 ? fake()->numberBetween(0, 3) : 0,
            'low_stock_threshold' => fake()->numberBetween(3, 6),

            'price_in_fils' => $priceInFils,
            'cost_price_in_fils' => (int) round(
                $priceInFils * fake()->randomFloat(2, 0.72, 0.85),
            ),

            'specs_en' => $specsEn,
            'specs_ar' => $specsAr,

            'brand_id' => $brand->id,
            'type_id' => $type->id,

            'featured_at' => $product['featured'] ?? false
                ? now()->subDays(fake()->numberBetween(1, 30))
                : null,
            'activated_at' => now()->subMonths(fake()->numberBetween(1, 14)),

            'created_at' => now()->subDays(fake()->numberBetween(5, 300)),
        ];
    }

    /**
     * Pair the seeded values with the type's spec definitions, so each locale's
     * value is stored under that locale's spec name.
     *
     * Every spec must carry a seeded value: a missing key would otherwise be
     * dropped from the product silently.
     *
     * @param  array<string, string|int|float|bool>  $values
     * @return array{0: array<string, string|int|float|bool>, 1: array<string, string|int|float|bool>}
     */
    private function localizedSpecs(array $values, Type $type): array
    {
        $specsEn = [];
        $specsAr = [];

        foreach ($type->specDefinitions as $spec) {
            if (! array_key_exists($spec->name_en, $values)) {
                throw new RuntimeException(
                    "The [{$spec->name_en}] spec of type [{$type->name}] has no seeded value.",
                );
            }

            $value = $values[$spec->name_en];

            [$valueEn, $valueAr] = is_string($value) && str_contains($value, '|')
                ? explode('|', $value, 2)
                : [$value, $value];

            $specsEn[$spec->name_en] = $valueEn;
            $specsAr[$spec->name_ar] = $valueAr;
        }

        return [$specsEn, $specsAr];
    }

    private function description(
        string $category,
        string $locale,
        int $index,
        string $name,
    ): string {
        $templates = self::DESCRIPTIONS[$category][$locale] ?? [':name'];

        return str_replace(
            ':name',
            $name,
            $templates[$index % count($templates)],
        );
    }
}
