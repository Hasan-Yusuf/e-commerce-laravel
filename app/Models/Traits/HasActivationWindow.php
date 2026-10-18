<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * The activation window shared by the rows that can be switched on and off
 * around a start and an end: the row is live once it has been activated and the
 * clock is inside its window, where either bound may be left open. The column
 * names differ between the consumers — a coupon expires, a discount ends — so
 * each names its own, and the query scope is kept here beside the model check
 * so the two can never drift apart.
 *
 * A model with no activation marker column says so by returning null from
 * activationColumn(): its window alone decides, and neither the model check nor
 * the scope mentions an activation column at all.
 */
trait HasActivationWindow
{
    public function isCurrentlyActive(): bool
    {
        $now = now();

        $activationColumn = $this->activationColumn();

        if (
            $activationColumn !== null
            && $this->getAttribute($activationColumn) === null
        ) {
            return false;
        }

        $startsAt = $this->getAttribute($this->activationStartColumn());
        $endsAt = $this->getAttribute($this->activationEndColumn());

        if ($startsAt !== null && $startsAt->isAfter($now)) {
            return false;
        }

        if ($endsAt !== null && $endsAt->isBefore($now)) {
            return false;
        }

        return true;
    }

    #[Scope]
    protected function currentlyActive(Builder $query): void
    {
        $now = now();

        $activationColumn = $this->activationColumn();
        $startsColumn = $this->activationStartColumn();
        $endsColumn = $this->activationEndColumn();

        if ($activationColumn !== null) {
            $query->whereNotNull($activationColumn);
        }

        $query
            ->where(
                static fn (Builder $query) => $query->whereNull($startsColumn)
                    ->orWhere($startsColumn, '<=', $now),
            )
            ->where(
                static fn (Builder $query) => $query->whereNull($endsColumn)
                    ->orWhere($endsColumn, '>=', $now),
            );
    }

    public function isActive(): Attribute
    {
        return Attribute::get($this->isCurrentlyActive(...));
    }

    /**
     * The column holding the moment the row was switched on, or null when the
     * model has no such column and its window alone decides.
     */
    abstract protected function activationColumn(): ?string;

    /**
     * The column holding the window's start; null in it means no lower bound.
     */
    abstract protected function activationStartColumn(): string;

    /**
     * The column holding the window's end; null in it means no upper bound.
     */
    abstract protected function activationEndColumn(): string;
}
