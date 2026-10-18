<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MimeType;
use App\Models\Traits\HasImageConversions;
use App\Models\Traits\HasLocalizedAttributes;
use App\Observers\CategoryObserver;
use Database\Factories\CategoryFactory;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property string $name_en
 * @property string $name_ar
 * @property string $slug
 * @property string|null $description_en
 * @property string|null $description_ar
 * @property int|null $parent_id
 * @property int $sort_order
 * @property Carbon|null $activated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $name
 * @property-read string|null $description
 * @property-read Category|null $parent
 * @property-read Collection<int, Category> $children
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, Type> $types
 * @property-read Collection<int, Brand> $brands
 * @property-read Collection<int, CartItem> $cartItems
 * @property-read Collection<int, OrderItem> $orderItems
 * @property-read Collection<int, Discount> $discounts
 */
#[ObservedBy(CategoryObserver::class)]
class Category extends Model implements HasMedia
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasImageConversions, HasLocalizedAttributes, InteractsWithMedia;

    protected $fillable = [
        'slug',

        'name_en',
        'name_ar',

        'description_en',
        'description_ar',

        'parent_id',
        'sort_order',
        'activated_at',

        // cast attributes
        'name',
    ];

    protected $appends = ['name', 'description'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(Product::class, Type::class);
    }

    public function types(): HasMany
    {
        return $this->hasMany(Type::class);
    }

    public function brands(): HasManyThrough
    {
        return $this->hasManyThrough(Brand::class, Product::class);
    }

    public function cartItems(): HasManyThrough
    {
        return $this->hasManyThrough(CartItem::class, Product::class);
    }

    public function orderItems(): HasManyThrough
    {
        return $this->hasManyThrough(OrderItem::class, Product::class);
    }

    public function discounts(): MorphToMany
    {
        return $this->morphToMany(Discount::class, 'discountable');
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('activated_at', '<=', now());
    }

    public function isActive(): bool
    {
        return $this->activated_at !== null && $this->activated_at <= now();
    }

    public function name(): Attribute
    {
        return Attribute::get(fn (): string => (string) $this->localizedValue('name'));
    }

    public function description(): Attribute
    {
        return Attribute::get(fn (): mixed => $this->localizedValue('description'));
    }

    #[\Override]
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('thumbnail')
            ->withResponsiveImages()
            ->singleFile();
    }

    #[\Override]
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addConversion(MimeType::IMAGE_WEBP);
    }
}
