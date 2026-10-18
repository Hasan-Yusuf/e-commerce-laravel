<?php

declare(strict_types=1);

namespace App\Enums\Traits;

trait CompareableEnum
{
    public function is(self|array|string $values): bool
    {
        if (\is_string($values)) {
            $values = static::tryFrom($values);
        }

        if (\is_array($values)) {
            return \in_array($this, $values, true);
        }

        return $this === $values;
    }

    public function isNot(self|array|string $values): bool
    {
        return ! $this->is($values);
    }
}
