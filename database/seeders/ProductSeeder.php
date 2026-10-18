<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Type;
use Database\Seeders\Concerns\SeedsSampleImages;
use Database\Seeders\Support\ProductAttributes;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    use SeedsSampleImages;

    public function __construct(
        private readonly ProductAttributes $productAttributes = new ProductAttributes,
    ) {}

    /**
     * Whether the catalogue's one sample picture has been laid down.
     */
    private bool $sampleImageSeeded = false;

    /**
     * A curated storefront catalogue, keyed by type key (see TypeSeeder::TYPES).
     *
     * Each product's `specs` are keyed by the type's English spec label and are
     * already shaped like the spec's `value_type`, so the seeder stores them as
     * they are — a number for `number`, a label for `options`, a boolean for
     * `boolean`, a string otherwise. Wrap a value as `English|Arabic` when the
     * two locales differ (colours, yes/no).
     *
     * @var array<string, array<int, array{name: string, brand: string, price: float, specs: array<string, string|int|float|bool>, featured?: bool}>>
     */
    private const PRODUCTS = [
        'smartphones' => [
            [
                'name' => 'Nova X5 Pro',
                'brand' => 'Nova',
                'price' => 429.0,
                'featured' => true,
                'specs' => [
                    'Screen Size' => 6.7,
                    'Storage' => 512,
                    'Battery' => 5000,
                    'Camera' => 'Triple 50 MP',
                    'Operating System' => 'Android 15',
                ],
            ],
            [
                'name' => 'Nova X5',
                'brand' => 'Nova',
                'price' => 289.0,
                'specs' => [
                    'Screen Size' => 6.5,
                    'Storage' => 256,
                    'Battery' => 4800,
                    'Camera' => 'Dual 50 MP',
                    'Operating System' => 'Android 15',
                ],
            ],
            [
                'name' => 'Solstice S10 Lite',
                'brand' => 'Solstice',
                'price' => 199.0,
                'specs' => [
                    'Screen Size' => 6.4,
                    'Storage' => 128,
                    'Battery' => 4500,
                    'Camera' => 'Dual 48 MP',
                    'Operating System' => 'Android 14',
                ],
            ],
        ],
        'audio' => [
            [
                'name' => 'Auralis ANC Pro',
                'brand' => 'Auralis',
                'price' => 89.0,
                'featured' => true,
                'specs' => [
                    'Battery Life' => 'Up to 30 hours|حتى 30 ساعة',
                    'Connectivity' => 'Bluetooth 5.3',
                    'Noise Cancelling' => true,
                    'Weight' => '254 g',
                ],
            ],
            [
                'name' => 'Meridian Studio Buds',
                'brand' => 'Meridian',
                'price' => 45.0,
                'specs' => [
                    'Battery Life' => 'Up to 8 hours|حتى 8 ساعات',
                    'Connectivity' => 'Bluetooth 5.2',
                    'Noise Cancelling' => false,
                    'Weight' => '48 g',
                ],
            ],
        ],
        'wearables' => [
            [
                'name' => 'Atlas Fit 2',
                'brand' => 'Atlas',
                'price' => 129.0,
                'featured' => true,
                'specs' => [
                    'Screen Size' => 1.43,
                    'Battery Life' => 'Up to 14 days|حتى 14 يوماً',
                    'Water Resistance' => '5 ATM',
                    'Color' => 'Black|أسود',
                ],
            ],
            [
                'name' => 'Lumina Watch S',
                'brand' => 'Lumina',
                'price' => 99.0,
                'specs' => [
                    'Screen Size' => 1.32,
                    'Battery Life' => 'Up to 7 days|حتى 7 أيام',
                    'Water Resistance' => '5 ATM',
                    'Color' => 'Silver|فضي',
                ],
            ],
        ],
        'kitchen-appliances' => [
            [
                'name' => 'Ember Blender Pro',
                'brand' => 'Ember',
                'price' => 79.0,
                'featured' => true,
                'specs' => [
                    'Capacity' => 1.5,
                    'Power' => 1200,
                    'Color' => 'Black|أسود',
                    'Material' => 'Stainless steel|ستانلس ستيل',
                ],
            ],
            [
                'name' => 'Harbor Electric Kettle',
                'brand' => 'Harbor',
                'price' => 29.0,
                'specs' => [
                    'Capacity' => 1.7,
                    'Power' => 2200,
                    'Color' => 'White|أبيض',
                    'Material' => 'Stainless steel|ستانلس ستيل',
                ],
            ],
        ],
        'cookware' => [
            [
                'name' => 'Terra Non-stick Set',
                'brand' => 'Terra',
                'price' => 69.0,
                'specs' => [
                    'Material' => 'Aluminium|ألمنيوم',
                    'Pieces' => 10,
                    'Dishwasher Safe' => true,
                    'Color' => 'Grey|رمادي',
                ],
            ],
            [
                'name' => 'Cascade Cast Iron Skillet',
                'brand' => 'Cascade',
                'price' => 39.0,
                'specs' => [
                    'Material' => 'Cast iron|حديد زهر',
                    'Pieces' => 1,
                    'Dishwasher Safe' => false,
                    'Color' => 'Black|أسود',
                ],
            ],
        ],
        'furniture' => [
            [
                'name' => 'Verdant Office Chair',
                'brand' => 'Verdant',
                'price' => 129.0,
                'featured' => true,
                'specs' => [
                    'Material' => 'Mesh|شبكة',
                    'Dimensions' => '120 x 60 x 60 cm',
                    'Color' => 'Black|أسود',
                    'Assembly Required' => true,
                ],
            ],
            [
                'name' => 'Atlas Bookshelf',
                'brand' => 'Atlas',
                'price' => 89.0,
                'specs' => [
                    'Material' => 'Wood|خشب',
                    'Dimensions' => '180 x 60 x 24 cm',
                    'Color' => 'Walnut|جوزي',
                    'Assembly Required' => true,
                ],
            ],
        ],
        'clothing' => [
            [
                'name' => 'Northwind Cotton Tee',
                'brand' => 'Northwind',
                'price' => 15.0,
                'specs' => [
                    'Material' => '100% cotton|قطن 100%',
                    'Size' => 'M',
                    'Color' => 'Navy|كحلي',
                    'Fit' => 'Regular|عادي',
                ],
            ],
            [
                'name' => 'Solstice Linen Shirt',
                'brand' => 'Solstice',
                'price' => 35.0,
                'specs' => [
                    'Material' => 'Linen|كتان',
                    'Size' => 'L',
                    'Color' => 'White|أبيض',
                    'Fit' => 'Relaxed|مريح',
                ],
            ],
        ],
        'footwear' => [
            [
                'name' => 'Meridian Running Shoe',
                'brand' => 'Meridian',
                'price' => 69.0,
                'featured' => true,
                'specs' => [
                    'Shoe Size' => 42,
                    'Material' => 'Mesh|شبكة',
                    'Color' => 'Blue|أزرق',
                    'Closure' => 'Lace-up|رباط',
                ],
            ],
            [
                'name' => 'Harbor Leather Loafer',
                'brand' => 'Harbor',
                'price' => 79.0,
                'specs' => [
                    'Shoe Size' => 43,
                    'Material' => 'Leather|جلد',
                    'Color' => 'Brown|بني',
                    'Closure' => 'Slip-on|بدون رباط',
                ],
            ],
        ],
        'bags' => [
            [
                'name' => 'Atlas Commuter Backpack',
                'brand' => 'Atlas',
                'price' => 49.0,
                'featured' => true,
                'specs' => [
                    'Material' => 'Polyester|بوليستر',
                    'Capacity' => 20,
                    'Color' => 'Black|أسود',
                    'Water Resistant' => true,
                ],
            ],
            [
                'name' => 'Lumina Tote',
                'brand' => 'Lumina',
                'price' => 39.0,
                'specs' => [
                    'Material' => 'Canvas|قماش',
                    'Capacity' => 12,
                    'Color' => 'Beige|بيج',
                    'Water Resistant' => false,
                ],
            ],
        ],
        'fitness' => [
            [
                'name' => 'Terra Yoga Mat',
                'brand' => 'Terra',
                'price' => 25.0,
                'specs' => [
                    'Material' => 'TPE',
                    'Weight' => '1.2 kg',
                    'Dimensions' => '183 x 61 x 0.6 cm',
                    'Color' => 'Purple|بنفسجي',
                ],
            ],
            [
                'name' => 'Atlas Dumbbell Set',
                'brand' => 'Atlas',
                'price' => 59.0,
                'featured' => true,
                'specs' => [
                    'Material' => 'Steel|فولاذ',
                    'Weight' => '10 kg',
                    'Dimensions' => 'Adjustable|قابل للتعديل',
                    'Color' => 'Black|أسود',
                ],
            ],
        ],
        'camping' => [
            [
                'name' => 'Cascade Dome Tent',
                'brand' => 'Cascade',
                'price' => 99.0,
                'featured' => true,
                'specs' => [
                    'Occupancy' => 4,
                    'Weight' => '3.5 kg',
                    'Material' => 'Polyester|بوليستر',
                    'Waterproof' => true,
                ],
            ],
            [
                'name' => 'Verdant Sleeping Bag',
                'brand' => 'Verdant',
                'price' => 45.0,
                'specs' => [
                    'Occupancy' => 1,
                    'Weight' => '1.8 kg',
                    'Material' => 'Nylon|نايلون',
                    'Waterproof' => false,
                ],
            ],
        ],
        'fiction' => [
            [
                'name' => 'The Last Lighthouse',
                'brand' => 'Northwind',
                'price' => 12.0,
                'featured' => true,
                'specs' => [
                    'Author' => 'Maya Reed|مايا ريد',
                    'Pages' => 320,
                    'Language' => 'English|الإنجليزية',
                    'Format' => 'Paperback|غلاف ورقي',
                ],
            ],
            [
                'name' => 'A Quiet Winter',
                'brand' => 'Northwind',
                'price' => 15.0,
                'specs' => [
                    'Author' => 'Daniel Brooks|دانيال بروكس',
                    'Pages' => 288,
                    'Language' => 'English|الإنجليزية',
                    'Format' => 'Hardcover|غلاف مقوى',
                ],
            ],
        ],
        'non-fiction' => [
            [
                'name' => 'The Art of Focus',
                'brand' => 'Meridian',
                'price' => 18.0,
                'specs' => [
                    'Author' => 'Lina Farah|لينا فرح',
                    'Pages' => 240,
                    'Language' => 'English|الإنجليزية',
                    'Format' => 'Paperback|غلاف ورقي',
                ],
            ],
            [
                'name' => 'Wanderlust: A Travel Journal',
                'brand' => 'Atlas',
                'price' => 22.0,
                'specs' => [
                    'Author' => 'Sara Nasser|سارة ناصر',
                    'Pages' => 192,
                    'Language' => 'English|الإنجليزية',
                    'Format' => 'Hardcover|غلاف مقوى',
                ],
            ],
        ],
        'skincare' => [
            [
                'name' => 'Verdant Hydrating Serum',
                'brand' => 'Verdant',
                'price' => 28.0,
                'featured' => true,
                'specs' => [
                    'Skin Type' => 'All skin types|جميع أنواع البشرة',
                    'Volume' => 30,
                    'Key Ingredient' => 'Hyaluronic acid|حمض الهيالورونيك',
                    'Fragrance Free' => true,
                ],
            ],
            [
                'name' => 'Lumina Gentle Cleanser',
                'brand' => 'Lumina',
                'price' => 16.0,
                'specs' => [
                    'Skin Type' => 'Sensitive|حساسة',
                    'Volume' => 150,
                    'Key Ingredient' => 'Aloe vera|الصبار',
                    'Fragrance Free' => true,
                ],
            ],
        ],
        'fragrance' => [
            [
                'name' => 'Ember Eau de Parfum',
                'brand' => 'Ember',
                'price' => 55.0,
                'featured' => true,
                'specs' => [
                    'Volume' => 50,
                    'Notes' => 'Amber and vanilla|عنبر وفانيليا',
                    'Longevity' => 'Long lasting|طويل الأمد',
                    'Gender' => 'Unisex|للجنسين',
                ],
            ],
            [
                'name' => 'Solstice Cologne',
                'brand' => 'Solstice',
                'price' => 42.0,
                'specs' => [
                    'Volume' => 75,
                    'Notes' => 'Citrus and cedar|حمضيات وأرز',
                    'Longevity' => 'Moderate|متوسط',
                    'Gender' => 'Men|رجالي',
                ],
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = TypeSeeder::flat();
        $sequence = 0;

        foreach (self::PRODUCTS as $typeKey => $products) {
            $definition = $types[$typeKey] ?? null;

            if ($definition === null) {
                continue;
            }

            $type = $this->resolveType(
                $definition['subcategory'],
                $definition['name'],
            );

            if ($type === null) {
                continue;
            }

            $type->loadMissing('specDefinitions');

            foreach ($products as $index => $product) {
                $sequence++;

                $brand = $this->resolveBrand($product['brand']);

                $attributes = $this->productAttributes->build(
                    $product,
                    $definition,
                    $type,
                    $brand,
                    ['index' => $index, 'sequence' => $sequence],
                );

                $this->seedProduct($attributes);
            }
        }
    }

    /**
     * Seed one product.
     *
     * The row is created through the product factory. A product that is already
     * seeded is only refreshed, so re-running the seeder neither duplicates the
     * row nor lays a second picture over the first.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function seedProduct(array $attributes): void
    {
        $existing = Product::query()
            ->where('slug', (string) $attributes['slug'])
            ->first();

        if ($existing !== null) {
            $existing->fill($attributes)->save();

            return;
        }

        $factory = Product::factory()->state($attributes);

        if ($this->shouldSeedSampleImage() && ! $this->sampleImageSeeded) {
            $factory = $factory->withThumbnailImage();

            $this->sampleImageSeeded = true;
        }

        $product = $factory->create();

        // created_at is not fillable, so the factory's insert stamped the row
        // with "now" rather than with the date the catalogue was seeded with.
        $product->forceFill(['created_at' => $attributes['created_at']])
            ->saveQuietly();
    }

    private function resolveType(string $categorySlug, string $typeName): ?Type
    {
        return Type::query()
            ->where('name', $typeName)
            ->whereHas(
                'category',
                static fn ($query) => $query->where('slug', $categorySlug),
            )
            ->first();
    }

    private function resolveBrand(string $name): Brand
    {
        return Brand::firstOrCreate(['name_en' => $name]);
    }
}
