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

class RefundOrderAction extends Action
{
    #[\Override]
    public static function getDefaultName(): ?string
    {
        return 'refund';
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(OrderResource::trans('actions.refund'))
            ->icon(Heroicon::OutlinedReceiptRefund)
            ->color(SemanticColor::Warning->value)
            ->tooltip(OrderResource::trans('actions.refund_tooltip'))
            ->visible(
                static fn (Order $record): bool => $record->status->canTransitionTo(
                    OrderStatus::REFUNDED,
                ),
            )
            ->requiresConfirmation()
            ->action(
                static fn (Order $record) => app(OrderService::class)->refundOrder(
                    $record,
                ),
            )
            ->successNotificationTitle(
                OrderResource::trans('notifications.refunded'),
            );
    }
}
