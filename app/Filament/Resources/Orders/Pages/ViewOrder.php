<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\Actions\CancelOrderAction;
use App\Filament\Resources\Orders\Actions\CollectPaymentOrderAction;
use App\Filament\Resources\Orders\Actions\ConfirmOrderAction;
use App\Filament\Resources\Orders\Actions\FulfilOrderAction;
use App\Filament\Resources\Orders\Actions\RefundOrderAction;
use App\Filament\Resources\Orders\Actions\ReinstateOrderAction;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Pages\Concerns\HasInfoRelations;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    use HasInfoRelations;

    protected static string $resource = OrderResource::class;

    /**
     * The shopper and their number, the addresses the order moves between, its
     * lines and the product each was bought from, and what was paid.
     *
     * @return array<int, string>
     */
    protected function infoRelations(): array
    {
        return [
            'user.phone.country',
            'shippingAddress.country',
            'billingAddress',
            'orderItems.product',
            'payments',
        ];
    }

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            FulfilOrderAction::make(),
            CancelOrderAction::make(),
            RefundOrderAction::make(),
            ConfirmOrderAction::make(),
            ReinstateOrderAction::make(),
            CollectPaymentOrderAction::make(),
            EditAction::make(),
        ];
    }
}
