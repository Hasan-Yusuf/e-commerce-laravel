<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DiscountType;
use App\Models\Traits\HasActivationWindow;
use App\Observers\DiscountObserver;
use Database\Factories\DiscountFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property DiscountType $type
 * @property numeric-string $value
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $activated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read bool $is_active
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, Category> $categories
 * @property-read Collection<int, Type> $productTypes
 * @property-read Collection<int, Brand> $brands
 */
#[ObservedBy(DiscountObserver::class)]
class Discount extends Model
{
    /** @use HasFactory<DiscountFactory> */
    use HasActivationWindow, HasFactory;

    protected $fillable = [
        'id',
        'name',

        'type',

        'value',

        'starts_at',
        'ends_at',

        'activated_at',
    ];

    protected $appends = ['is_active'];

    #[\Override]
    public function casts(): array
    {
        return [
            'type' => DiscountType::class,

            'value' => 'decimal:3',

            'starts_at' => 'datetime',

            'ends_at' => 'datetime',

            'activated_at' => 'datetime',
        ];
    }

    public function products(): MorphToMany
    {
        return $this->morphedByMany(Product::class, 'discountable');
    }

    public function categories(): MorphToMany
    {
        return $this->morphedByMany(Category::class, 'discountable');
    }

    public function productTypes(): MorphToMany
    {
        return $this->morphedByMany(Type::class, 'discountable');
    }

    public function brands(): MorphToMany
    {
        return $this->morphedByMany(Brand::class, 'discountable');
    }

    protected function activationColumn(): ?string
    {
        return 'activated_at';
    }

    protected function activationStartColumn(): string
    {
        return 'starts_at';
    }

    protected function activationEndColumn(): string
    {
        return 'ends_at';
    }
}
