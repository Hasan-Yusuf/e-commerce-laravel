<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\OrderStatus;
use App\Enums\SemanticColor;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\Order\OrderService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class ReinstateOrderAction extends Action
{
    #[\Override]
    public static function getDefaultName(): ?string
    {
        return 'reinstate';
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(OrderResource::trans('actions.reinstate'))
            ->icon(Heroicon::ArrowUturnLeft)
            ->color(SemanticColor::Info->value)
            ->tooltip(OrderResource::trans('actions.reinstate_tooltip'))
            ->visible(
                static fn (Order $record): bool => $record->status->canTransitionTo(
                    OrderStatus::PENDING,
                ),
            )
            ->requiresConfirmation()
            ->action(
                static fn (Order $record) => app(OrderService::class)->reinstateOrder(
                    $record,
                ),
            )
            ->successNotificationTitle(
                OrderResource::trans('notifications.reinstated'),
            );
    }
}
