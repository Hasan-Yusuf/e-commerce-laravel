<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\SpecFilterValuesObserver;
use Database\Factories\TypeFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int $category_id
 * @property bool|null $specs_need_review
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read array<int, string> $specs
 * @property-read Category|null $category
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, Discount> $discounts
 * @property-read Collection<int, TypeSpec> $specDefinitions
 */
#[ObservedBy(SpecFilterValuesObserver::class)]
class Type extends Model
{
    /** @use HasFactory<TypeFactory> */
    use HasFactory;

    protected $fillable = ['category_id', 'name'];

    protected $appends = ['specs'];

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function casts(): array
    {
        return [
            'specs_need_review' => 'bool',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function discounts(): MorphToMany
    {
        return $this->morphToMany(Discount::class, 'discountable');
    }

    /**
     * The typed specification schema that this type's products must supply.
     */
    public function specDefinitions(): HasMany
    {
        return $this
            ->hasMany(TypeSpec::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * The localized spec names, in definition order.
     */
    public function specs(): Attribute
    {
        return Attribute::get(
            fn (): array => $this->specDefinitions
                ->map(static fn (TypeSpec $spec): string => $spec->name)
                ->values()
                ->all(),
        );
    }

    /**
     * Whether the spec schema changed under the products that were filled in
     * against it and they have not been gone through since. Raising and
     * clearing the flag is the catalogue's to do (ProductSpecSyncService); this
     * only reads it back.
     */
    public function needsSpecReview(): bool
    {
        // Cast rather than read: a type that has just been created has no value
        // for the column yet, since only the database applied its default.
        return (bool) $this->specs_need_review;
    }
}
