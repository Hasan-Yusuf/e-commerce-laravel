<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AsMoney;
use App\Enums\MimeType;
use App\Enums\StockStatus;
use App\Models\Traits\HasImageConversions;
use App\Models\Traits\HasProductAttributes;
use App\Models\Traits\HasProductScopes;
use App\Observers\SpecFilterValuesObserver;
use App\Support\Money;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $meta_name
 * @property string $sku
 * @property string|null $description_en
 * @property string|null $description_ar
 * @property string|null $meta_description
 * @property float|null $weight
 * @property int $stock
 * @property int|null $low_stock_threshold
 * @property int $reserved_stock
 * @property Money $price_in_fils
 * @property Money $cost_price_in_fils
 * @property array<string, mixed>|null $specs_en
 * @property array<string, mixed>|null $specs_ar
 * @property int|null $brand_id
 * @property int|null $type_id
 * @property Carbon|null $featured_at
 * @property Carbon|null $activated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read string|null $description
 * @property-read array<string, mixed> $specs
 * @property-read Brand|null $brand
 * @property-read Category|null $category
 * @property-read Collection<int, CartItem> $cartItems
 * @property-read Collection<int, OrderItem> $orderItems
 * @property-read Type|null $type
 * @property-read Collection<int, Discount> $discounts
 */
#[ObservedBy(SpecFilterValuesObserver::class)]
class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasImageConversions, HasProductAttributes, HasProductScopes, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'slug',

        'name',
        'meta_name',

        'sku',

        'description_en',
        'description_ar',
        'meta_description',

        'weight',

        'stock',
        'reserved_stock',
        'low_stock_threshold',

        'price_in_fils',
        'cost_price_in_fils',

        'specs_en',
        'specs_ar',

        'type_id',
        'brand_id',

        'featured_at',
        'activated_at',
    ];

    protected $appends = ['description', 'specs'];

    #[\Override]
    public function casts()
    {
        return [
            'specs_en' => 'array',
            'specs_ar' => 'array',

            'price_in_fils' => AsMoney::class,
            'cost_price_in_fils' => AsMoney::class,

            'featured_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): HasOneThrough
    {
        return $this->hasOneThrough(
            Category::class,
            Type::class,
            'id',
            'id',
            'type_id',
            'category_id',
        );
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function discounts(): MorphToMany
    {
        return $this->morphToMany(Discount::class, 'discountable');
    }

    /**
     * The sellable quantity, in PHP: the same on-hand-less-reserved figure the
     * stock scopes filter on (HasProductScopes::AVAILABLE_STOCK_EXPRESSION).
     */
    public function getAvailableStock(): int
    {
        return max(0, $this->stock - $this->reserved_stock);
    }

    public function isLowOnStock(): bool
    {
        return $this->getAvailableStock() <= $this->low_stock_threshold;
    }

    /**
     * The storefront availability state derived from stock levels.
     */
    public function stockStatus(): StockStatus
    {
        if ($this->getAvailableStock() <= 0) {
            return StockStatus::OUT_OF_STOCK;
        }

        if ($this->isLowOnStock()) {
            return StockStatus::LOW_STOCK;
        }

        return StockStatus::IN_STOCK;
    }

    public function isFeatured(): bool
    {
        return $this->featured_at !== null && $this->featured_at <= now();
    }

    public function isActive(): bool
    {
        return $this->activated_at !== null && $this->activated_at <= now();
    }

    /**
     * Whether an active discount currently applies to this product, through
     * the product itself, its type, its category, or its brand.
     *
     * This runs a single EXISTS query (the discounted() scope) and is meant for
     * one-off checks on a single product. For product cards/lists prefer the
     * pricing already carried by ProductCardData, which reuses
     * ProductPricingService so it is not re-queried per card.
     */
    public function isDiscounted(): bool
    {
        return static::query()
            ->whereKey($this->getKey())
            ->discounted()
            ->exists();
    }

    #[\Override]
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('thumbnail')
            ->withResponsiveImages()
            ->singleFile();

        $this->addMediaCollection('gallery')
            ->withResponsiveImages();
    }

    #[\Override]
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addConversion(MimeType::IMAGE_WEBP);
    }

    /**
     * The product's gallery images, excluding the thumbnail.
     */
    public function getGalleryMedia(): MediaCollection
    {
        return $this->getMedia('gallery');
    }
}
