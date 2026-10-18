<?php

declare(strict_types=1);

namespace App\Filament\Tables\Filters;

use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filters a table by a "from" and "until" date range against a single
 * datetime column (default `created_at`). The column and labels are
 * configurable so the filter can be reused on any table.
 */
class DateRangeFilter extends Filter
{
    protected string $column = 'created_at';

    protected string|Closure|null $fromLabel = null;

    protected string|Closure|null $untilLabel = null;

    public function column(string $column): static
    {
        $this->column = $column;

        return $this;
    }

    public function fromLabel(string|Closure $label): static
    {
        $this->fromLabel = $label;

        return $this;
    }

    public function untilLabel(string|Closure $label): static
    {
        $this->untilLabel = $label;

        return $this;
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->schema([
            DatePicker::make('from')
                ->label(fn (): string => $this->fromLabel ?? __('From')),

            DatePicker::make('until')
                ->label(fn (): string => $this->untilLabel ?? __('Until')),
        ])
            ->query(fn (Builder $query, array $data): Builder => $query->when(
                $data['from'] ?? null,
                fn (Builder $query, $date) => $query->whereDate(
                    $this->column,
                    '>=',
                    $date,
                ),
            )
                ->when(
                    $data['until'] ?? null,
                    fn (Builder $query, $date) => $query->whereDate(
                        $this->column,
                        '<=',
                        $date,
                    ),
                ));
    }
}
