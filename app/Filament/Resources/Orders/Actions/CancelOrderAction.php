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

class CancelOrderAction extends Action
{
    #[\Override]
    public static function getDefaultName(): ?string
    {
        return 'cancel';
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(OrderResource::trans('actions.cancel'))
            ->icon(Heroicon::XMark)
            ->color(SemanticColor::Danger->value)
            ->tooltip(OrderResource::trans('actions.cancel_tooltip'))
            ->visible(
                static fn (Order $record): bool => $record->status->canTransitionTo(
                    OrderStatus::CANCELLED,
                ),
            )
            ->requiresConfirmation()
            ->action(
                static fn (Order $record) => app(OrderService::class)->cancelOrder(
                    $record,
                ),
            )
            ->successNotificationTitle(
                OrderResource::trans('notifications.cancelled'),
            );
    }
}
