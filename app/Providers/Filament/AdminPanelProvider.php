<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Enums\SemanticColor;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\DashboardStatsOverview;
use App\Filament\Widgets\LowStockProducts;
use App\Filament\Widgets\OrdersRequiringAttention;
use App\Filament\Widgets\RecentOrders;
use App\Filament\Widgets\RevenueChart;
use App\Http\Middleware\AuthenticateAdminPanel;
use App\Http\Middleware\SetLocaleMiddleware;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Infolists\Components\Entry;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Enums\Platform;
use Filament\Support\Enums\Size;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    #[\Override]
    public function panel(Panel $panel): Panel
    {
        $panel = self::myCustomizations($panel);

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->assets([
                // Scopes pasting a clipboard image to one upload, panel-wide:
                // FilePond otherwise hands a paste to every upload on the page.
                Js::make('scoped-file-paste', resource_path('js/filament/scoped-file-paste.js')),
            ])
            ->colors([
                SemanticColor::Primary->value => 'oklch(0.7599 0.1009 244.66)',
                SemanticColor::Success->value => 'oklch(0.6997 0.0482 159.81)',
                SemanticColor::Info->value => 'oklch(0.446 0.043 257.28)',
                SemanticColor::Warning->value => 'oklch(0.7038 0.0503 100.97)',
                SemanticColor::Danger->value => 'oklch(0.6964 0.0509 29.55)',
            ])
            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\Filament\Resources',
            )
            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: 'App\Filament\Pages',
            )
            ->pages([Dashboard::class])
            ->widgets([
                DashboardStatsOverview::class,
                RevenueChart::class,
                OrdersRequiringAttention::class,
                LowStockProducts::class,
                RecentOrders::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocaleMiddleware::class,
            ])
            ->authMiddleware([AuthenticateAdminPanel::class]);
    }

    public function myCustomizations(Panel $panel)
    {
        FilamentTimezone::set('UTC');
        Table::configureUsing(static function (Table $table) {
            $table->modifyUngroupedRecordActionsUsing(
                static fn (Action $action) => $action->button()->size(Size::ExtraSmall),
            );
        });
        ActionGroup::configureUsing(
            static fn (ActionGroup $group) => $group
                ->button()
                ->size(Size::ExtraSmall)
                ->color(SemanticColor::Gray->value),
        );

        Column::configureUsing(
            static fn (Column $column): Column => $column->placeholder('-'),
        );

        Entry::configureUsing(
            static fn (Entry $entry): Entry => $entry->placeholder('-'),
        );

        $panel
            ->resourceCreatePageRedirect('index')
            ->resourceEditPageRedirect('index')
            ->unsavedChangesAlerts()
            ->sidebarCollapsibleOnDesktop()
            ->spa()
            ->brandName('Nexus')
            ->databaseNotifications()
            ->databaseNotificationsPolling('15s')
            ->globalSearchKeyBindings(['ctrl+k', 'command+k'])
            ->globalSearchFieldSuffix(
                static fn (): ?string => match (Platform::detect()) {
                    Platform::Windows, Platform::Linux => 'CTRL+K',
                    Platform::Mac => '⌘K',
                    default => null,
                },
            )
            ->globalSearchDebounce('750ms')
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                static fn (): string => Blade::render(
                    '@livewire(\'language-switcher\')',
                ),
            );

        return $panel;
    }
}
