<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CacheKeys;
use App\Models\Product;
use App\Models\Type;
use App\Models\TypeSpec;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * The catalogue's filter values are cached per category scope, and they are made
 * of a product's spec values, its type's category and the type's spec
 * definitions — so a write to one of those drops the whole cache.
 */
class SpecFilterValuesObserver
{
    /**
     * The attributes whose change can alter the filter values. A null list means
     * every change on the model matters (a spec definition is the value's shape).
     *
     * @var array<class-string<Model>, array<int, string>|null>
     */
    private const array WATCHED = [
        Product::class => ['specs_en', 'specs_ar', 'type_id', 'activated_at'],
        Type::class => ['category_id'],
        TypeSpec::class => null,
    ];

    public function saved(Model $model): void
    {
        if ($this->isRelevant($model)) {
            $this->flush();
        }
    }

    public function deleted(Model $model): void
    {
        $this->flush();
    }

    private function isRelevant(Model $model): bool
    {
        if (! array_key_exists($model::class, self::WATCHED)) {
            return true;
        }

        $watched = self::WATCHED[$model::class];

        return $watched === null || $model->wasRecentlyCreated || $model->wasChanged($watched);
    }

    private function flush(): void
    {
        Cache::forget(CacheKeys::SpecFilterValues);
    }
}
