<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use BackedEnum;
use Closure;
use Illuminate\Contracts\Support\Htmlable;

/**
 * One of the two states a switch column action moves a record between: the value
 * written for that state, and how the action presents it.
 *
 * Immutable because Filament clones an action per record; mutating a side in
 * place would leak one record's configuration into every clone sharing it.
 */
final readonly class ColumnSwitchSide
{
    /**
     * @param  string | Htmlable | Closure(): (string | Htmlable | null) | null  $label
     * @param  string | BackedEnum | Htmlable | Closure(): (string | BackedEnum | Htmlable | null) | null  $icon
     * @param  string | array<string> | Closure(): (string | array<string> | null) | null  $color
     * @param  string | Closure(): (string | null) | null  $notification
     * @param  string | Htmlable | Closure(): (string | Htmlable | null) | null  $tooltip
     */
    public function __construct(
        public mixed $value = null,
        public string|Htmlable|Closure|null $label = null,
        public string|BackedEnum|Htmlable|Closure|null $icon = null,
        public string|array|Closure|null $color = null,
        public string|Closure|null $notification = null,
        public string|Htmlable|Closure|null $tooltip = null,
    ) {}

    public function withValue(mixed $value): self
    {
        return $this->copy(['value' => $value]);
    }

    public function withLabel(string|Htmlable|Closure|null $label): self
    {
        return $this->copy(['label' => $label]);
    }

    public function withIcon(string|BackedEnum|Htmlable|Closure|null $icon): self
    {
        return $this->copy(['icon' => $icon]);
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function withColor(string|array|Closure|null $color): self
    {
        return $this->copy(['color' => $color]);
    }

    public function withNotification(string|Closure|null $notification): self
    {
        return $this->copy(['notification' => $notification]);
    }

    public function withTooltip(string|Htmlable|Closure|null $tooltip): self
    {
        return $this->copy(['tooltip' => $tooltip]);
    }

    /**
     * A copy of this side with the given properties replaced — the one place
     * that has to know every property a copy carries.
     *
     * @param  array<string, mixed>  $changes
     */
    private function copy(array $changes): self
    {
        return new self(
            ...array_merge(
                [
                    'value' => $this->value,
                    'label' => $this->label,
                    'icon' => $this->icon,
                    'color' => $this->color,
                    'notification' => $this->notification,
                    'tooltip' => $this->tooltip,
                ],
                $changes,
            ),
        );
    }
}
