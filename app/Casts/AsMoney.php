<?php

declare(strict_types=1);

namespace App\Casts;

use App\Support\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * An integer-fils column read and written as a Money.
 *
 * Every money column in the application is stored in whole fils, so this cast is
 * the one boundary where that integer becomes a Money and back. A value that
 * arrives already in fils — from the calculator, a factory or a seeder — is
 * passed straight through, so nothing has to wrap an amount it already holds in
 * the right unit.
 *
 * Object caching is off because these columns also have their own BHD
 * accessors (Product::price, Order::subtotal, ...) that write the raw fils
 * column directly. A cached Money would go stale the moment one of those
 * accessors wrote a new amount underneath it.
 *
 * @implements CastsAttributes<Money, Money|int|float|string|null>
 */
class AsMoney implements CastsAttributes, SerializesCastableAttributes
{
    public bool $withoutObjectCaching = true;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): Money
    {
        return Money::fromFils((int) ($value ?? 0));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return self::toFils($value);
    }

    /**
     * A Money or an amount already in fils as the integer the column holds.
     */
    private static function toFils(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Money) {
            return $value->toFils();
        }

        if (is_int($value) || is_float($value) || is_string($value) && is_numeric($value)) {
            return (int) round((float) $value);
        }

        throw new InvalidArgumentException(
            'Expected a '.Money::class.' instance or an amount in fils, got '.get_debug_type($value).'.',
        );
    }

    /**
     * The column reads back as the amount in BHD, so a model turned into an
     * array — a form filled from a record, a Livewire payload — carries a
     * plain number rather than a Money object.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function serialize(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value instanceof Money ? $value->toAmount() : $value;
    }
}
