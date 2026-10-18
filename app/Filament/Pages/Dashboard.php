<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\DashboardPeriod;
use App\Support\HasTranslationKeys;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

/**
 * The panel home. Adds one shared period filter on top of Filament's dashboard;
 * the widgets read it through InteractsWithPageFilters, so a single change moves
 * every period-scoped figure at once. HasFiltersForm persists the choice in the
 * session, so the administrator's window survives navigation.
 */
class Dashboard extends BaseDashboard
{
    use HasFiltersForm, HasTranslationKeys;

    public static function getNavigationLabel(): string
    {
        return self::trans('title');
    }

    public function getTitle(): string|Htmlable
    {
        return self::trans('title');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('period')
                            ->label(self::trans('filters.period'))
                            ->options(DashboardPeriod::class)
                            ->default(DashboardPeriod::default()->value)
                            ->selectablePlaceholder(false)
                            ->live(),
                    ]),
            ]);
    }
}
