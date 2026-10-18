<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

/**
 * Switches a model column of a record between an "on" and an "off" value.
 *
 * The label, icon, and color describe the state that the column will be
 * switched to, and the opposite of the record's current state is written when
 * the action is executed. Boolean columns switch between `true` and `false`,
 * while date columns switch between `now()` and `null`.
 *
 * The column type is inferred from the model's cast for the column, which is
 * the name of the action, and can be overridden with `boolean()` or `date()`.
 * The resolution itself lives in {@see ColumnSwitcher}, which this action
 * delegates to.
 */
class SwitchColumnAction extends Action
{
    protected ColumnSwitchSide $onState;

    protected ColumnSwitchSide $offState;

    protected ?bool $columnIsBoolean = null;

    /**
     * The state that the column was last switched to.
     */
    protected ?bool $switchedOn = null;

    #[\Override]
    public static function getDefaultName(): ?string
    {
        return 'switch';
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->onState = new ColumnSwitchSide(label: 'Switch On', color: 'success');
        $this->offState = new ColumnSwitchSide(label: 'Switch Off', color: 'danger');

        $this
            ->label(fn (Model $record): string|Htmlable|null => $this->getSwitchLabel($this->directionFor($record)))
            ->icon(fn (Model $record): string|BackedEnum|Htmlable|null => $this->getSwitchIcon(
                $this->directionFor($record),
            ))
            ->color(fn (Model $record): string|array|null => $this->getSwitchColor($this->directionFor($record)))
            ->tooltip(fn (Model $record): string|Htmlable|null => $this->getSwitchTooltip($this->directionFor($record)))
            ->action(fn (Model $record) => $this->switchRecord($record, $this->directionFor($record)))
            ->successNotificationTitle($this->getSwitchNotificationTitle(...));
    }

    /**
     * Set the value that is written when the column is switched on.
     */
    public function onValue(mixed $onValue): static
    {
        $this->onState = $this->onState->withValue($onValue);

        return $this;
    }

    /**
     * Set the value that is written when the column is switched off.
     */
    public function offValue(mixed $offValue): static
    {
        $this->offState = $this->offState->withValue($offValue);

        return $this;
    }

    /**
     * Treat the column as a boolean column.
     */
    public function boolean(): static
    {
        $this->columnIsBoolean = true;

        return $this;
    }

    /**
     * Treat the column as a date column.
     */
    public function date(): static
    {
        $this->columnIsBoolean = false;

        return $this;
    }

    /**
     * Set the label for switching the column on.
     */
    public function onLabel(string|Htmlable|Closure|null $label): static
    {
        $this->onState = $this->onState->withLabel($label);

        return $this;
    }

    /**
     * Set the label for switching the column off.
     */
    public function offLabel(string|Htmlable|Closure|null $label): static
    {
        $this->offState = $this->offState->withLabel($label);

        return $this;
    }

    /**
     * Set the icon for switching the column on.
     */
    public function onIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->onState = $this->onState->withIcon($icon);

        return $this;
    }

    /**
     * Set the icon for switching the column off.
     */
    public function offIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->offState = $this->offState->withIcon($icon);

        return $this;
    }

    /**
     * Set the color for switching the column on.
     *
     * @param  string | array<string> | Closure | null  $color
     */
    public function onColor(string|array|Closure|null $color): static
    {
        $this->onState = $this->onState->withColor($color);

        return $this;
    }

    /**
     * Set the color for switching the column off.
     *
     * @param  string | array<string> | Closure | null  $color
     */
    public function offColor(string|array|Closure|null $color): static
    {
        $this->offState = $this->offState->withColor($color);

        return $this;
    }

    /**
     * Set the success notification title for switching the column on.
     */
    public function onNotification(string|Closure|null $title): static
    {
        $this->onState = $this->onState->withNotification($title);

        return $this;
    }

    /**
     * Set the success notification title for switching the column off.
     */
    public function offNotification(string|Closure|null $title): static
    {
        $this->offState = $this->offState->withNotification($title);

        return $this;
    }

    /**
     * Set the tooltip for switching the column on.
     */
    public function onTooltip(string|Htmlable|Closure|null $tooltip): static
    {
        $this->onState = $this->onState->withTooltip($tooltip);

        return $this;
    }

    /**
     * Set the tooltip for switching the column off.
     */
    public function offTooltip(string|Htmlable|Closure|null $tooltip): static
    {
        $this->offState = $this->offState->withTooltip($tooltip);

        return $this;
    }

    /**
     * Determine whether the column is treated as a boolean column.
     */
    public function isBooleanColumn(?Model $record = null): bool
    {
        return $this->switcher()->isBooleanColumn($record);
    }

    /**
     * Determine whether the record's column currently holds its "on" value.
     */
    public function isOn(Model $record): bool
    {
        return $this->switcher()->isOn($record);
    }

    /**
     * Determine whether the next switch writes the "on" value.
     */
    public function willSwitchOn(Model $record): bool
    {
        return $this->switcher()->willSwitchOn($record);
    }

    /**
     * The direction the record's column will be switched in.
     */
    public function directionFor(Model $record): SwitchDirection
    {
        return $this->switcher()->directionFor($record);
    }

    /**
     * Resolve the value that switches the record's column on.
     */
    public function getOnValue(?Model $record = null): mixed
    {
        return $this->switcher()->onValue($record);
    }

    /**
     * Resolve the value that switches the record's column off.
     */
    public function getOffValue(?Model $record = null): mixed
    {
        return $this->switcher()->offValue($record);
    }

    /**
     * Resolve the label for the state the column will be switched to.
     */
    public function getSwitchLabel(SwitchDirection $direction): string|Htmlable|null
    {
        $label = $this->evaluateSwitchSide(
            $direction->pick($this->onState, $this->offState)->label,
        );

        return is_string($label) || $label instanceof Htmlable ? $label : null;
    }

    /**
     * Resolve the icon for the state the column will be switched to.
     */
    public function getSwitchIcon(SwitchDirection $direction): string|BackedEnum|Htmlable|null
    {
        $icon = $this->evaluateSwitchSide(
            $direction->pick($this->onState, $this->offState)->icon,
        );

        return is_string($icon) || $icon instanceof BackedEnum || $icon instanceof Htmlable
            ? $icon
            : null;
    }

    /**
     * Resolve the color for the state the column will be switched to.
     *
     * @return string | array<string> | null
     */
    public function getSwitchColor(SwitchDirection $direction): string|array|null
    {
        $color = $this->evaluateSwitchSide(
            $direction->pick($this->onState, $this->offState)->color,
        );

        return is_string($color) || is_array($color) ? $color : null;
    }

    /**
     * Resolve the tooltip for the state the column will be switched to.
     */
    public function getSwitchTooltip(SwitchDirection $direction): string|Htmlable|null
    {
        $tooltip = $this->evaluateSwitchSide(
            $direction->pick($this->onState, $this->offState)->tooltip,
        );

        return is_string($tooltip) || $tooltip instanceof Htmlable ? $tooltip : null;
    }

    /**
     * Evaluate one of the switch side's presentational values. The parameter is
     * typed `mixed` so a label/icon/color union is not mistaken for a closure
     * by the analyzer; the caller narrows the result.
     */
    private function evaluateSwitchSide(mixed $value): mixed
    {
        return $this->evaluate($value);
    }

    /**
     * Write the given state to the record's column, remembering which
     * direction was applied so that the success notification can describe it.
     */
    protected function switchRecord(Model $record, SwitchDirection $direction): void
    {
        $this->switchedOn = $direction->isOn();

        $record->update([
            (string) $this->getName() => $this->switcher()->valueFor($record),
        ]);
    }

    /**
     * Resolve the success notification title for the direction that the
     * action was last executed in.
     */
    protected function getSwitchNotificationTitle(): ?string
    {
        return $this->evaluate(
            ($this->switchedOn ? $this->onState : $this->offState)->notification,
        );
    }

    /**
     * Bind the action's current sides and mode to the resolver that does the
     * work, so the action can stay a thin Filament wrapper.
     */
    private function switcher(): ColumnSwitcher
    {
        return new ColumnSwitcher(
            $this->getName(),
            $this->onState,
            $this->offState,
            $this->columnIsBoolean,
            fn (mixed $value, ?Model $record = null): mixed => $this->evaluate(
                $value,
                ['record' => $record],
            ),
        );
    }
}
