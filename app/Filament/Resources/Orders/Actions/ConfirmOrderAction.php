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

class ConfirmOrderAction extends Action
{
    #[\Override]
    public static function getDefaultName(): ?string
    {
        return 'confirm';
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(OrderResource::trans('actions.confirm'))
            ->icon(Heroicon::Check)
            ->color(SemanticColor::Success->value)
            ->tooltip(OrderResource::trans('actions.confirm_tooltip'))
            ->visible(
                static fn (Order $record): bool => $record->status->canTransitionTo(
                    OrderStatus::PROCESSING,
                ),
            )
            ->requiresConfirmation()
            ->action(
                static fn (Order $record) => app(OrderService::class)->confirmOrder(
                    $record,
                ),
            )
            ->successNotificationTitle(
                OrderResource::trans('notifications.confirmed'),
            );
    }
}
