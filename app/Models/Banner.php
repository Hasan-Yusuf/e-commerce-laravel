<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MimeType;
use App\Models\Traits\HasImageConversions;
use App\Models\Traits\HasLocalizedAttributes;
use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property string $title_en
 * @property string $title_ar
 * @property string|null $link
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $title
 */
class Banner extends Model implements HasMedia
{
    /** @use HasFactory<BannerFactory> */
    use HasFactory, HasImageConversions, HasLocalizedAttributes, InteractsWithMedia;

    protected $fillable = ['title_en', 'title_ar', 'link'];

    protected $appends = ['title'];

    public function title(): Attribute
    {
        return Attribute::get(fn (): string => (string) $this->localizedValue('title'));
    }

    #[\Override]
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('image')
            ->withResponsiveImages()
            ->singleFile();
    }

    #[\Override]
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addConversion(MimeType::IMAGE_WEBP);
    }
}
