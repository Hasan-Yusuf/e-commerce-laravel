<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MimeType;
use App\Models\Traits\HasImageConversions;
use App\Models\Traits\HasLocalizedAttributes;
use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property string $name_en
 * @property string $name_ar
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $name
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, Discount> $discounts
 */
class Brand extends Model implements HasMedia
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory, HasImageConversions, HasLocalizedAttributes, InteractsWithMedia;

    protected $fillable = [
        'name_en',
        'name_ar',
    ];

    protected $appends = ['name'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function discounts(): MorphToMany
    {
        return $this->morphToMany(Discount::class, 'discountable');
    }

    public function name(): Attribute
    {
        return Attribute::get(fn (): mixed => $this->localizedValue('name'));
    }

    /**
     * One mark per colour scheme: the light-scheme logo is the dark mark the
     * shop draws on its light pages, the dark-scheme logo the light mark it
     * draws on its dark ones. Either may be left out, in which case the brand
     * name stands in for it on that scheme.
     */
    #[\Override]
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('logo_light')
            ->withResponsiveImages()
            ->singleFile();

        $this
            ->addMediaCollection('logo_dark')
            ->withResponsiveImages()
            ->singleFile();
    }

    #[\Override]
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addConversion(MimeType::IMAGE_WEBP);
    }
}
