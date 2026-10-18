<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\Order\OrderService;
use Filament\Resources\Pages\CreateRecord;

/**
 * @extends CreateRecord<Order>
 */
class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * The billing address field is hidden while "shipping and billing are the
     * same" is on, so it is never dehydrated and has to mirror the shipping
     * address here.
     *
     * Everything else is derived from the integer-fils columns: the OrderItem
     * model turns each line's unit price and quantity into `*_in_fils`, and the
     * Order model recomputes its total from the persisted subtotal, tax,
     * shipping and discount.
     */
    #[\Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['shipping_and_billing_same'] ?? false) {
            $data['billing_address_id'] = $data['shipping_address_id'];
        }

        unset($data['shipping_and_billing_same']);

        return $data;
    }

    /**
     * The lines were written by the form, not by a checkout, so the hold they
     * take is derived afterwards — the same recalculation a placed order gets.
     */
    protected function afterCreate(): void
    {
        $order = $this->getRecord();

        if ($order === null) {
            return;
        }

        app(OrderService::class)->reserveOrder($order);
    }
}
