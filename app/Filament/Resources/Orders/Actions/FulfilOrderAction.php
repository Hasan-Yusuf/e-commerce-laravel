<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\SemanticColor;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Pipelines\Orders\OrderFulfiller;
use App\Services\Order\OrderService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class FulfilOrderAction extends Action
{
    #[\Override]
    public static function getDefaultName(): ?string
    {
        return 'fulfil';
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(OrderResource::trans('actions.fulfil'))
            ->icon(Heroicon::Truck)
            ->color(SemanticColor::Success->value)
            ->tooltip(OrderResource::trans('actions.fulfil_tooltip'))
            ->visible(
                static fn (Order $record): bool => $record->status->canTransitionTo(
                    OrderFulfiller::fulfilmentStatusFor($record),
                ),
            )
            ->action(
                static fn (Order $record) => app(OrderService::class)->fulfilOrder(
                    $record,
                ),
            )
            ->successNotificationTitle(
                OrderResource::trans('notifications.fulfilled'),
            );
    }
}
