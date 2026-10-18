<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\PaymentStatus;
use App\Enums\SemanticColor;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\Payments\PaymentService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class CollectPaymentOrderAction extends Action
{
    #[\Override]
    public static function getDefaultName(): ?string
    {
        return 'mark_collected';
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(OrderResource::trans('actions.mark_collected'))
            ->icon(Heroicon::Banknotes)
            ->color(SemanticColor::Success->value)
            ->tooltip(OrderResource::trans('actions.mark_collected_tooltip'))
            ->visible(
                static fn (Order $record): bool => $record
                    ->payments()
                    ->where('status', PaymentStatus::PENDING)
                    ->exists(),
            )
            ->requiresConfirmation()
            ->action(
                static fn (Order $record) => app(PaymentService::class)->settle(
                    $record,
                ),
            )
            ->successNotificationTitle(
                OrderResource::trans('notifications.payment_collected'),
            );
    }
}
