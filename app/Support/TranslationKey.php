<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;
use Stringable;

/**
 * The translation key prefix a class reads its strings from, assembled from the
 * parts this app used to keep in separate traits: a `filament/` folder, literal
 * segments, and a directory or file derived from the class name.
 *
 * Left to itself it mirrors the conventions the app already had — a resource
 * resolves to `filament/{resource}`, an enum to `enums.{enum}`, and any other
 * class to its own snake-cased name under `filament/`. A class points elsewhere
 * with forFilament() and setKey(); a panel widget sharing the dashboard's file is
 * the case that needs it.
 *
 * Note the two separators, which Laravel distinguishes: `/` steps into a
 * directory (`lang/en/filament/order.php`), while `.` steps into an array inside
 * one file (`lang/en/enums.php['order_status']`).
 */
final class TranslationKey implements Stringable
{
    private string $group = '';

    private function __construct(
        private readonly string $class,
    ) {}

    public static function for(string $class): self
    {
        return new self($class);
    }

    /**
     * Prefix the group with the admin panel's `filament/` folder.
     */
    public function forFilament(): self
    {
        return $this->appendFile('filament');
    }

    /**
     * Append one file segment to the group.
     */
    public function setKey(string $key): self
    {
        return $this->appendFile($key);
    }

    /**
     * Derive the group from the class name, dropping a `Resource` suffix.
     */
    public function resource(): self
    {
        return $this->forFilament()->appendFile(
            Str::snake(Str::beforeLast(class_basename($this->class), 'Resource')),
        );
    }

    /**
     * Derive the group from the class name: every enum lives in one `enums.php`
     * file, keyed by its own snake-cased name.
     */
    public function enum(): self
    {
        $this->group = 'enums.'.Str::snake(class_basename($this->class));

        return $this;
    }

    public function group(): string
    {
        if ($this->group !== '') {
            return $this->group;
        }

        return enum_exists($this->class) ? $this->enum()->group() : $this->resource()->group();
    }

    public function __toString(): string
    {
        return $this->group();
    }

    private function appendFile(string $key): self
    {
        $this->group = $this->group === '' ? $key : $this->group.'/'.$key;

        return $this;
    }
}
