<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * The product's own localized attributes: the specs and the description the
 * panel enters per locale.
 */
trait HasProductAttributes
{
    use HasLocalizedAttributes;

    public function specs(): Attribute
    {
        return Attribute::get(fn (): array => $this->localizedValue('specs', []));
    }

    public function description(): Attribute
    {
        return Attribute::get(fn (): mixed => $this->localizedValue('description'));
    }
}
