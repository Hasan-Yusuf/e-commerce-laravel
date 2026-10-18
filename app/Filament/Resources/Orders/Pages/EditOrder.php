<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\Order\OrderService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

/**
 * @extends EditRecord<Order>
 */
class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * Mirrors the create flow: while "shipping and billing are the same" is on,
     * the hidden billing field is not dehydrated, so it follows the shipping one.
     */
    #[\Override]
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['shipping_and_billing_same'] ?? false) {
            $data['billing_address_id'] = $data['shipping_address_id'];
        }

        unset($data['shipping_and_billing_same']);

        return $data;
    }

    /**
     * Lines added, re-quantified or removed by the form change the hold the
     * order takes, so it is derived again once the form has written them.
     */
    protected function afterSave(): void
    {
        app(OrderService::class)->reserveOrder($this->getRecord());
    }

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
