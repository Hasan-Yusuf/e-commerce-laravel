<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SpecFilterType;
use App\Enums\SpecValueType;
use App\Models\Category;
use App\Models\Type;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    /**
     * Product types grouped by category slug, each with its spec schema.
     *
     * The spec map is `English label => Arabic label`; the array key order is
     * the order products must supply their spec values in (see ProductSeeder).
     *
     * @var array<string, array<string, array{name: string, specs: array<string, string>}>>
     */
    public const TYPES = [
        'electronics' => [
            'smartphones' => [
                'name' => 'Smartphones',
                'specs' => [
                    'Screen Size' => 'حجم الشاشة',
                    'Storage' => 'التخزين',
                    'Battery' => 'البطارية',
                    'Camera' => 'الكاميرا',
                    'Operating System' => 'نظام التشغيل',
                ],
            ],
            'audio' => [
                'name' => 'Headphones',
                'specs' => [
                    'Battery Life' => 'عمر البطارية',
                    'Connectivity' => 'الاتصال',
                    'Noise Cancelling' => 'إلغاء الضوضاء',
                    'Weight' => 'الوزن',
                ],
            ],
            'wearables' => [
                'name' => 'Smartwatches',
                'specs' => [
                    'Screen Size' => 'حجم الشاشة',
                    'Battery Life' => 'عمر البطارية',
                    'Water Resistance' => 'مقاومة الماء',
                    'Color' => 'اللون',
                ],
            ],
        ],
        'home-kitchen' => [
            'kitchen-appliances' => [
                'name' => 'Kitchen Appliances',
                'specs' => [
                    'Capacity' => 'السعة',
                    'Power' => 'الطاقة',
                    'Color' => 'اللون',
                    'Material' => 'المواد',
                ],
            ],
            'cookware' => [
                'name' => 'Cookware',
                'specs' => [
                    'Material' => 'المواد',
                    'Pieces' => 'القطع',
                    'Dishwasher Safe' => 'آمن لغسالة الصحون',
                    'Color' => 'اللون',
                ],
            ],
            'furniture' => [
                'name' => 'Furniture',
                'specs' => [
                    'Material' => 'المواد',
                    'Dimensions' => 'الأبعاد',
                    'Color' => 'اللون',
                    'Assembly Required' => 'يتطلب تركيب',
                ],
            ],
        ],
        'fashion' => [
            'clothing' => [
                'name' => 'Clothing',
                'specs' => [
                    'Material' => 'المواد',
                    'Size' => 'المقاس',
                    'Color' => 'اللون',
                    'Fit' => 'القَصّة',
                ],
            ],
            'footwear' => [
                'name' => 'Footwear',
                'specs' => [
                    'Shoe Size' => 'المقاس',
                    'Material' => 'المواد',
                    'Color' => 'اللون',
                    'Closure' => 'الإغلاق',
                ],
            ],
            'bags' => [
                'name' => 'Bags',
                'specs' => [
                    'Material' => 'المواد',
                    'Capacity' => 'السعة',
                    'Color' => 'اللون',
                    'Water Resistant' => 'مقاوم للماء',
                ],
            ],
        ],
        'sports-outdoors' => [
            'fitness' => [
                'name' => 'Fitness',
                'specs' => [
                    'Material' => 'المواد',
                    'Weight' => 'الوزن',
                    'Dimensions' => 'الأبعاد',
                    'Color' => 'اللون',
                ],
            ],
            'camping' => [
                'name' => 'Camping',
                'specs' => [
                    'Occupancy' => 'السعة',
                    'Weight' => 'الوزن',
                    'Material' => 'المواد',
                    'Waterproof' => 'مقاوم للماء',
                ],
            ],
        ],
        'books' => [
            'fiction' => [
                'name' => 'Fiction',
                'specs' => [
                    'Author' => 'المؤلف',
                    'Pages' => 'الصفحات',
                    'Language' => 'اللغة',
                    'Format' => 'الغلاف',
                ],
            ],
            'non-fiction' => [
                'name' => 'Non-fiction',
                'specs' => [
                    'Author' => 'المؤلف',
                    'Pages' => 'الصفحات',
                    'Language' => 'اللغة',
                    'Format' => 'الغلاف',
                ],
            ],
        ],
        'beauty' => [
            'skincare' => [
                'name' => 'Skincare',
                'specs' => [
                    'Skin Type' => 'نوع البشرة',
                    'Volume' => 'الحجم',
                    'Key Ingredient' => 'المكوّن الرئيسي',
                    'Fragrance Free' => 'خالٍ من العطر',
                ],
            ],
            'fragrance' => [
                'name' => 'Fragrance',
                'specs' => [
                    'Volume' => 'الحجم',
                    'Notes' => 'النوتات',
                    'Longevity' => 'الثبات',
                    'Gender' => 'الجنس',
                ],
            ],
        ],
    ];

    /**
     * Which subcategory (category slug) each type is filed under. The parent
     * category page aggregates its children through `descendantIds()`.
     *
     * @var array<string, string>
     */
    public const SUBCATEGORY = [
        'smartphones' => 'smartphones',
        'audio' => 'audio',
        'wearables' => 'wearables',
        'kitchen-appliances' => 'kitchen-appliances',
        'cookware' => 'cookware',
        'furniture' => 'furniture',
        'clothing' => 'clothing',
        'footwear' => 'footwear',
        'bags' => 'bags',
        'fitness' => 'fitness',
        'camping' => 'camping',
        'fiction' => 'fiction',
        'non-fiction' => 'non-fiction',
        'skincare' => 'skincare',
        'fragrance' => 'fragrance',
    ];

    /**
     * Typing for the well-known spec labels: the value type, display unit and
     * storefront filter settings seeded for demo data. Labels missing here are
     * seeded as plain, non-filterable text.
     *
     * @var array<string, array{value_type: SpecValueType, unit?: string, filter_type?: SpecFilterType}>
     */
    public const SPEC_TYPES = [
        'Screen Size' => [
            'value_type' => SpecValueType::NUMBER,
            'unit' => 'in',
            'filter_type' => SpecFilterType::RANGE,
        ],
        'Storage' => ['value_type' => SpecValueType::NUMBER, 'unit' => 'GB', 'filter_type' => SpecFilterType::RANGE],
        'Battery' => ['value_type' => SpecValueType::NUMBER, 'unit' => 'mAh', 'filter_type' => SpecFilterType::RANGE],
        'Capacity' => ['value_type' => SpecValueType::NUMBER, 'unit' => 'L', 'filter_type' => SpecFilterType::RANGE],
        'Power' => ['value_type' => SpecValueType::NUMBER, 'unit' => 'W', 'filter_type' => SpecFilterType::RANGE],
        'Pieces' => ['value_type' => SpecValueType::NUMBER, 'filter_type' => SpecFilterType::RANGE],
        'Shoe Size' => ['value_type' => SpecValueType::NUMBER, 'filter_type' => SpecFilterType::RANGE],
        'Volume' => ['value_type' => SpecValueType::NUMBER, 'unit' => 'ml', 'filter_type' => SpecFilterType::RANGE],
        'Pages' => ['value_type' => SpecValueType::NUMBER, 'filter_type' => SpecFilterType::RANGE],
        'Occupancy' => ['value_type' => SpecValueType::NUMBER, 'filter_type' => SpecFilterType::RANGE],
        'Noise Cancelling' => ['value_type' => SpecValueType::BOOLEAN, 'filter_type' => SpecFilterType::LIST],
        'Dishwasher Safe' => ['value_type' => SpecValueType::BOOLEAN, 'filter_type' => SpecFilterType::LIST],
        'Assembly Required' => ['value_type' => SpecValueType::BOOLEAN, 'filter_type' => SpecFilterType::LIST],
        'Water Resistant' => ['value_type' => SpecValueType::BOOLEAN, 'filter_type' => SpecFilterType::LIST],
        'Waterproof' => ['value_type' => SpecValueType::BOOLEAN, 'filter_type' => SpecFilterType::LIST],
        'Fragrance Free' => ['value_type' => SpecValueType::BOOLEAN, 'filter_type' => SpecFilterType::LIST],
    ];

    /**
     * The types flattened to `type key => definition`, for seeders that need to
     * resolve a type, its subcategory and its spec labels by key.
     *
     * @return array<string, array{category: string, subcategory: string, name: string, specs: array<string, string>}>
     */
    public static function flat(): array
    {
        $flat = [];

        foreach (self::TYPES as $category => $types) {
            foreach ($types as $key => $type) {
                $flat[$key] = [
                    'category' => $category,
                    'subcategory' => self::SUBCATEGORY[$key] ?? $category,
                    'name' => $type['name'],
                    'specs' => $type['specs'],
                ];
            }
        }

        return $flat;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::TYPES as $categorySlug => $types) {
            foreach ($types as $typeKey => $type) {
                $subcategorySlug = self::SUBCATEGORY[$typeKey] ?? $categorySlug;

                $category = Category::query()->where('slug', $subcategorySlug)->first() ?? Category::query()
                    ->where('slug', $categorySlug)
                    ->first();

                if ($category === null) {
                    continue;
                }

                $model = Type::updateOrCreate([
                    'category_id' => $category->id,
                    'name' => $type['name'],
                ]);

                $this->syncSpecDefinitions($model, $type['specs']);
            }
        }
    }

    /**
     * Replace the type's typed spec definitions, keeping the seeded English =>
     * Arabic label order.
     *
     * @param  array<string, string>  $specs  English label => Arabic label
     */
    private function syncSpecDefinitions(Type $type, array $specs): void
    {
        $type->specDefinitions()->delete();

        $rows = [];

        foreach (array_keys($specs) as $sortOrder => $nameEn) {
            $typing = self::SPEC_TYPES[$nameEn] ?? [];
            $filterType = $typing['filter_type'] ?? null;

            $rows[] = [
                'name_en' => $nameEn,
                'name_ar' => $specs[$nameEn],
                'value_type' => $typing['value_type'] ?? SpecValueType::TEXT,
                'options' => null,
                'unit' => $typing['unit'] ?? null,
                'is_filterable' => $filterType !== null,
                'filter_type' => $filterType,
                'sort_order' => $sortOrder,
            ];
        }

        if ($rows !== []) {
            $type->specDefinitions()->createMany($rows);
        }
    }
}
