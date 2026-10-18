<?php

declare(strict_types=1);

namespace App\Models\Traits;

/**
 * Reads a model's per-locale columns as the one value the current locale shows:
 * the current locale's, or the fallback locale's when the current one is empty.
 * The attribute's base name is the accessor's own (`name`, `description`,
 * `title`, `specs`), so its columns are `name_en`, `name_ar`, and so on.
 */
trait HasLocalizedAttributes
{
    protected function localizedValue(string $attribute, mixed $default = null): mixed
    {
        $value = $this->getAttribute($attribute.'_'.app()->getLocale());

        if ($value !== null) {
            return $value;
        }

        return $this->getAttribute($attribute.'_'.app()->getFallbackLocale()) ?? $default;
    }
}
