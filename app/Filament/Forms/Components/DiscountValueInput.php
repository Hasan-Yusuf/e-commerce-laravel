<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use App\Enums\DiscountType;
use App\Enums\Unit;
use Closure;
use Filament\Schemas\Components\Utilities\Get;

/**
 * A discount's value, entered in the unit its type gives it: a share of the
 * price as a percentage, an amount off it as money. The type lives in a sibling
 * field, so the unit shown is only ever one of the two a discount allows — and
 * the cap that goes with the type comes with it.
 */
class DiscountValueInput extends UnitInput
{
    protected string|Closure $typeField = 'type';

    /**
     * The sibling field holding the discount type, for a form that names it
     * something other than `type`.
     */
    public function typeField(string|Closure $field): static
    {
        $this->typeField = $field;

        return $this;
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->unit(fn (Get $get): Unit => $this->typeIn($get)->unit())
            ->maxValue(fn (Get $get): ?float => $this->typeIn($get)->maxValue());
    }

    /**
     * The type the form currently holds. A form being filled carries the enum
     * already; a submission that left it out carries nothing, and reads as the
     * default so the unit still has an answer.
     */
    private function typeIn(Get $get): DiscountType
    {
        /** @var DiscountType|null $type */
        $type = $get->enum(
            (string) $this->evaluate($this->typeField),
            DiscountType::class,
        );

        return $type ?? DiscountType::FIXED_AMOUNT;
    }
}
