<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * The storefront category tree for a general-purpose online store.
     *
     * Rows with a `parent` are subcategories of that top-level slug. Types are
     * attached to the subcategories (see TypeSeeder), while parent category
     * pages aggregate their children through `Category::descendantIds()`.
     *
     * @var array<int, array{slug: string, parent: ?string, name_en: string, name_ar: string, description_en: string, description_ar: string}>
     */
    private const CATEGORIES = [
        // ── Top-level categories ──
        [
            'slug' => 'electronics',
            'parent' => null,
            'name_en' => 'Electronics',
            'name_ar' => 'الإلكترونيات',
            'description_en' => 'Phones, audio, wearables and everyday electronics.',
            'description_ar' => 'الهواتف والصوتيات والأجهزة القابلة للارتداء والإلكترونيات اليومية.',
        ],
        [
            'slug' => 'home-kitchen',
            'parent' => null,
            'name_en' => 'Home & Kitchen',
            'name_ar' => 'المنزل والمطبخ',
            'description_en' => 'Appliances, cookware and furniture for the whole home.',
            'description_ar' => 'أجهزة وأدوات طهي وأثاث للمنزل بأكمله.',
        ],
        [
            'slug' => 'fashion',
            'parent' => null,
            'name_en' => 'Fashion',
            'name_ar' => 'الأزياء',
            'description_en' => 'Clothing, footwear and bags for every style.',
            'description_ar' => 'ملابس وأحذية وحقائب لكل الأذواق.',
        ],
        [
            'slug' => 'sports-outdoors',
            'parent' => null,
            'name_en' => 'Sports & Outdoors',
            'name_ar' => 'الرياضة والهواء الطلق',
            'description_en' => 'Fitness gear and outdoor equipment.',
            'description_ar' => 'معدات اللياقة البدنية ومستلزمات الهواء الطلق.',
        ],
        [
            'slug' => 'books',
            'parent' => null,
            'name_en' => 'Books',
            'name_ar' => 'الكتب',
            'description_en' => 'Fiction and non-fiction for every reader.',
            'description_ar' => 'كتب روائية وغير روائية لكل قارئ.',
        ],
        [
            'slug' => 'beauty',
            'parent' => null,
            'name_en' => 'Beauty',
            'name_ar' => 'الجمال',
            'description_en' => 'Skincare, fragrance and personal care.',
            'description_ar' => 'العناية بالبشرة والعطور والعناية الشخصية.',
        ],

        // ── Subcategories ──
        [
            'slug' => 'smartphones',
            'parent' => 'electronics',
            'name_en' => 'Smartphones',
            'name_ar' => 'الهواتف الذكية',
            'description_en' => 'Latest smartphones from the brands we trust.',
            'description_ar' => 'أحدث الهواتف الذكية من علامات موثوقة.',
        ],
        [
            'slug' => 'audio',
            'parent' => 'electronics',
            'name_en' => 'Audio',
            'name_ar' => 'الصوتيات',
            'description_en' => 'Headphones, earbuds and speakers for every listener.',
            'description_ar' => 'سماعات الرأس والأذن ومكبرات الصوت لكل مستمع.',
        ],
        [
            'slug' => 'wearables',
            'parent' => 'electronics',
            'name_en' => 'Wearables',
            'name_ar' => 'الأجهزة القابلة للارتداء',
            'description_en' => 'Smartwatches and fitness bands for an active life.',
            'description_ar' => 'ساعات ذكية وأساور لياقة لحياة نشطة.',
        ],
        [
            'slug' => 'kitchen-appliances',
            'parent' => 'home-kitchen',
            'name_en' => 'Kitchen Appliances',
            'name_ar' => 'أجهزة المطبخ',
            'description_en' => 'Blenders, kettles and appliances that make cooking easier.',
            'description_ar' => 'خلاطات وغلايات وأجهزة تجعل الطهي أسهل.',
        ],
        [
            'slug' => 'cookware',
            'parent' => 'home-kitchen',
            'name_en' => 'Cookware',
            'name_ar' => 'أدوات الطهي',
            'description_en' => 'Pots, pans and bakeware for everyday cooking.',
            'description_ar' => 'قدور ومقالي وأدوات خبز للطهي اليومي.',
        ],
        [
            'slug' => 'furniture',
            'parent' => 'home-kitchen',
            'name_en' => 'Furniture',
            'name_ar' => 'الأثاث',
            'description_en' => 'Chairs, tables and storage for every room.',
            'description_ar' => 'كراسي وطاولات ووحدات تخزين لكل غرفة.',
        ],
        [
            'slug' => 'clothing',
            'parent' => 'fashion',
            'name_en' => 'Clothing',
            'name_ar' => 'الملابس',
            'description_en' => 'Everyday wear from casual to formal.',
            'description_ar' => 'ملابس يومية من الكاجوال إلى الرسمية.',
        ],
        [
            'slug' => 'footwear',
            'parent' => 'fashion',
            'name_en' => 'Footwear',
            'name_ar' => 'الأحذية',
            'description_en' => 'Shoes and trainers for work and play.',
            'description_ar' => 'أحذية رياضية وكلاسيكية للعمل واللعب.',
        ],
        [
            'slug' => 'bags',
            'parent' => 'fashion',
            'name_en' => 'Bags',
            'name_ar' => 'الحقائب',
            'description_en' => 'Backpacks, totes and travel bags.',
            'description_ar' => 'حقائب ظهر وحقائب كتف وحقائب سفر.',
        ],
        [
            'slug' => 'fitness',
            'parent' => 'sports-outdoors',
            'name_en' => 'Fitness',
            'name_ar' => 'اللياقة البدنية',
            'description_en' => 'Weights, mats and training accessories.',
            'description_ar' => 'أثقال وحصائر ومستلزمات التمرين.',
        ],
        [
            'slug' => 'camping',
            'parent' => 'sports-outdoors',
            'name_en' => 'Camping',
            'name_ar' => 'التخييم',
            'description_en' => 'Tents, sleeping gear and outdoor essentials.',
            'description_ar' => 'خيام ومستلزمات النوم وأساسيات التخييم.',
        ],
        [
            'slug' => 'fiction',
            'parent' => 'books',
            'name_en' => 'Fiction',
            'name_ar' => 'الروايات',
            'description_en' => 'Novels and short stories.',
            'description_ar' => 'روايات وقصص قصيرة.',
        ],
        [
            'slug' => 'non-fiction',
            'parent' => 'books',
            'name_en' => 'Non-fiction',
            'name_ar' => 'الكتب غير الروائية',
            'description_en' => 'Biography, history and self-improvement.',
            'description_ar' => 'السير الذاتية والتاريخ وتطوير الذات.',
        ],
        [
            'slug' => 'skincare',
            'parent' => 'beauty',
            'name_en' => 'Skincare',
            'name_ar' => 'العناية بالبشرة',
            'description_en' => 'Cleansers, moisturisers and treatments.',
            'description_ar' => 'منظفات ومرطبات وعلاجات.',
        ],
        [
            'slug' => 'fragrance',
            'parent' => 'beauty',
            'name_en' => 'Fragrance',
            'name_ar' => 'العطور',
            'description_en' => 'Perfumes and colognes for every occasion.',
            'description_ar' => 'عطور وكلونيا لكل مناسبة.',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $order = 0;

        foreach (self::CATEGORIES as $category) {
            if ($category['parent'] !== null) {
                continue;
            }

            $order++;

            Category::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name_en' => $category['name_en'],
                    'name_ar' => $category['name_ar'],
                    'description_en' => $category['description_en'],
                    'description_ar' => $category['description_ar'],
                    'parent_id' => null,
                    'sort_order' => $order,
                    'activated_at' => now()->subMonths(6),
                ],
            );
        }

        $order = 0;

        foreach (self::CATEGORIES as $category) {
            if ($category['parent'] === null) {
                continue;
            }

            $order++;

            $parent = Category::query()->where('slug', $category['parent'])->first();

            Category::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name_en' => $category['name_en'],
                    'name_ar' => $category['name_ar'],
                    'description_en' => $category['description_en'],
                    'description_ar' => $category['description_ar'],
                    'parent_id' => $parent?->id,
                    'sort_order' => 100 + $order,
                    'activated_at' => now()->subMonths(6),
                ],
            );
        }
    }
}
