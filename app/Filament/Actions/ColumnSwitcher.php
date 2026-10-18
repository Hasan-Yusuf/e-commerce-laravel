<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The behaviour behind SwitchColumnAction: which direction a record's column
 * moves in, the value written, and whether the column is read as a boolean or a
 * date. Holding it apart keeps the Filament action a thin wrapper and this
 * resolution independent of it.
 */
final class ColumnSwitcher
{
    /**
     * The Eloquent casts that identify a column as holding a date.
     */
    private const DATE_CASTS = [
        'date',
        'datetime',
        'immutable_date',
        'immutable_datetime',
        'timestamp',
    ];

    /**
     * @param  string | null  $column  The column being switched, null when the action has no name
     * @param  Closure(mixed, ?Model=): mixed  $evaluate  Evaluates a side's closure, bound to the action
     */
    public function __construct(
        private readonly ?string $column,
        private readonly ColumnSwitchSide $on,
        private readonly ColumnSwitchSide $off,
        private readonly ?bool $columnIsBoolean,
        private readonly Closure $evaluate,
    ) {}

    /**
     * Whether the record's column currently holds its "on" value.
     */
    public function isOn(Model $record): bool
    {
        $value = $this->column === null
            ? null
            : $record->getAttribute($this->column);

        if ($this->isBooleanColumn($record)) {
            return (bool) $value === (bool) $this->onValue($record);
        }

        if ($value === null) {
            return false;
        }

        return Carbon::make($value)->lessThanOrEqualTo(now());
    }

    /**
     * Whether the next switch writes the "on" value.
     */
    public function willSwitchOn(Model $record): bool
    {
        return ! $this->isOn($record);
    }

    /**
     * The direction the record's column will be switched in.
     */
    public function directionFor(Model $record): SwitchDirection
    {
        return $this->willSwitchOn($record)
            ? SwitchDirection::On
            : SwitchDirection::Off;
    }

    /**
     * The value the record's column is moved to.
     */
    public function valueFor(Model $record): mixed
    {
        return $this->directionFor($record)->isOn()
            ? $this->onValue($record)
            : $this->offValue($record);
    }

    /**
     * Resolve the value that switches the record's column on.
     */
    public function onValue(?Model $record = null): mixed
    {
        if ($this->on->value !== null) {
            return ($this->evaluate)($this->on->value, $record);
        }

        return $this->isBooleanColumn($record) ? true : now();
    }

    /**
     * Resolve the value that switches the record's column off.
     */
    public function offValue(?Model $record = null): mixed
    {
        if ($this->off->value !== null) {
            return ($this->evaluate)($this->off->value, $record);
        }

        return $this->isBooleanColumn($record) ? false : null;
    }

    /**
     * Determine whether the column is treated as a boolean column.
     */
    public function isBooleanColumn(?Model $record = null): bool
    {
        if ($this->columnIsBoolean !== null) {
            return $this->columnIsBoolean;
        }

        return $this->column === null || ! ($record?->hasCast($this->column, self::DATE_CASTS) ?? false);
    }
}
