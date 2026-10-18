<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    #[\Override]
    public function getTabs(): array
    {
        $counts = Order::query()
            ->toBase()
            ->select('status')
            ->selectExpression('COUNT(*)', 'count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $tabs = [
            'all' => Tab::make('all')
                ->label(OrderResource::trans('tabs.all'))
                ->icon(Heroicon::OutlinedSquare2Stack),
        ];

        foreach (OrderStatus::cases() as $status) {
            $tabs[$status->value] = Tab::make($status->getLabel())
                ->modifyQueryUsing(
                    static fn (Builder $query) => $query->where('status', $status),
                )
                ->icon($status->getIcon())
                ->badgeColor($status->getColor())
                ->badge($counts->get($status->value, 0));
        }

        return $tabs;
    }
}
