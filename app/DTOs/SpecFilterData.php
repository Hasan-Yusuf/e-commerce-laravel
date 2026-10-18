<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\SpecFilterType;
use App\Enums\SpecValueType;

/**
 * One filterable spec as the storefront renders it: the localized spec name,
 * the control its `filter_type` asks for, and — for a list — the values the
 * products being listed actually carry.
 */
final readonly class SpecFilterData
{
    /**
     * @param  array<string, string>  $values  Stored value => displayed label.
     */
    public function __construct(
        public int $id,
        public string $name,
        public SpecFilterType $filterType,
        public SpecValueType $valueType,
        public ?string $unit = null,
        public array $values = [],
    ) {}

    /**
     * Whether the spec narrows by a min/max pair rather than a list of values.
     */
    public function isRange(): bool
    {
        return $this->filterType->is(SpecFilterType::RANGE);
    }

    /**
     * Whether the spec answers yes or no. Its list is the two answers, which are
     * worth showing outright rather than behind a dropdown that would hide them
     * for the sake of holding them.
     */
    public function isBoolean(): bool
    {
        return $this->valueType->is(SpecValueType::BOOLEAN);
    }

    /**
     * Whether the spec has nothing to offer in this scope: a list the products
     * never fill in.
     */
    public function isEmpty(): bool
    {
        return ! $this->isRange() && $this->values === [];
    }

    /**
     * The group title: the spec name, carrying the unit it is measured in.
     */
    public function title(): string
    {
        return $this->unit === null
            ? $this->name
            : "{$this->name} ({$this->unit})";
    }
}
