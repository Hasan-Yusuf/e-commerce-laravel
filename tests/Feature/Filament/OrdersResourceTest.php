<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\AddressType;
use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Filament\Exports\OrderExporter;
use App\Filament\Resources\Orders\Actions\CancelOrderAction;
use App\Filament\Resources\Orders\Actions\CollectPaymentOrderAction;
use App\Filament\Resources\Orders\Actions\ConfirmOrderAction;
use App\Filament\Resources\Orders\Actions\FulfilOrderAction;
use App\Filament\Resources\Orders\Actions\RefundOrderAction;
use App\Filament\Resources\Orders\Actions\ReinstateOrderAction;
use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin panel coverage for the Orders resource.
 *
 * The table exposes no DeleteAction, so deletion is exercised through the
 * DeleteAction on the EditOrder header. Orders carry no relation managers, so
 * their lines are read through the order's own schemas rather than a manager.
 *
 * An order created through the panel mirrors the shipping address onto the billing
 * address while "shipping and billing are the same" is on, because the hidden billing
 * field is not dehydrated. Totals are stored as integer fils and are asserted in the
 * create tests below.
 */
class OrdersResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_order_exporter_is_pointed_at_the_order_model(): void
    {
        // An export runs against the exporter's own model, so a class name that
        // does not resolve would only surface when somebody exported.
        static::assertSame(Order::class, OrderExporter::getModel());
    }

    public function test_it_lists_orders(): void
    {
        $this->actingAsAdmin();

        $orders = Order::factory()->count(3)->create();

        Livewire::test(ListOrders::class)
            ->assertOk()
            ->assertCanSeeTableRecords($orders);
    }

    public function test_it_creates_an_order(): void
    {
        $this->actingAsAdmin();

        $user = User::factory()->create(['role' => UserRole::CUSTOMER]);

        $address = Address::factory()
            ->user($user)
            ->addressType(AddressType::SHIPPING)
            ->create();

        $product = Product::factory()->create([
            'name' => 'Sample Product',
            'stock' => 100,
        ]);

        Livewire::test(CreateOrder::class)
            ->fillForm([
                'user_id' => $user->id,
                'status' => OrderStatus::PENDING,
                'delivery_method' => DeliveryMethod::STANDARD,
                'shipping_and_billing_same' => true,
                'shipping_address_id' => $address->id,
                'order_items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                        'unit_price_in_fils' => 10,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $order = Order::query()->where('user_id', $user->id)->sole();

        static::assertSame($address->id, $order->shipping_address_id);
        // The hidden billing field follows the shipping address.
        static::assertSame($address->id, $order->billing_address_id);
        static::assertSame(OrderStatus::PENDING, $order->status);

        // 2 × 10.000 BHD must persist as exact integer fils.
        static::assertSame(20_000, $order->subtotal_in_fils->toFils());
        static::assertSame(0, $order->tax_amount_in_fils->toFils());
        static::assertSame(20_000, $order->total_amount_in_fils->toFils());

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price_in_fils' => 10_000,
            'total_price_in_fils' => 20_000,
        ]);

        // The line was written by the form rather than by a checkout, so the
        // hold it takes is derived once the order exists.
        static::assertSame(2, (int) $product->refresh()->reserved_stock);
    }

    public function test_it_keeps_a_separate_billing_address_when_requested(): void
    {
        $this->actingAsAdmin();

        $user = User::factory()->create(['role' => UserRole::CUSTOMER]);

        $shippingAddress = Address::factory()
            ->user($user)
            ->addressType(AddressType::SHIPPING)
            ->create();

        $billingAddress = Address::factory()
            ->user($user)
            ->addressType(AddressType::BILLING)
            ->create();

        $product = Product::factory()->create(['stock' => 100]);

        Livewire::test(CreateOrder::class)
            ->fillForm([
                'user_id' => $user->id,
                'status' => OrderStatus::PENDING,
                'delivery_method' => DeliveryMethod::PICKUP,
                'shipping_and_billing_same' => false,
                'shipping_address_id' => $shippingAddress->id,
                'billing_address_id' => $billingAddress->id,
                'order_items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'unit_price_in_fils' => 5,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $order = Order::query()->where('user_id', $user->id)->sole();

        static::assertSame($shippingAddress->id, $order->shipping_address_id);
        static::assertSame($billingAddress->id, $order->billing_address_id);
        static::assertSame(5_000, $order->total_amount_in_fils->toFils());

        // The admin records how the order reaches the shopper, a collection
        // included.
        static::assertSame(DeliveryMethod::PICKUP, $order->delivery_method);
    }

    public function test_it_searches_orders_by_shipping_address(): void
    {
        $this->actingAsAdmin();

        $user = User::factory()->create(['role' => UserRole::CUSTOMER]);

        $address = Address::factory()
            ->user($user)
            ->addressType(AddressType::SHIPPING)
            ->create([
                'city' => 'Distinctive City',
                'state' => 'Distinctive State',
            ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
        ]);

        $otherAddress = Address::factory()
            ->addressType(AddressType::SHIPPING)
            ->create([
                'city' => 'Somewhere Else',
                'state' => 'Somewhere Else',
            ]);

        $otherOrder = Order::factory()->create([
            'shipping_address_id' => $otherAddress->id,
            'billing_address_id' => $otherAddress->id,
        ]);

        Livewire::test(ListOrders::class)
            ->searchTable('Distinctive City')
            ->assertCanSeeTableRecords([$order])
            ->assertCanNotSeeTableRecords([$otherOrder]);
    }

    public function test_it_views_an_order_with_its_items(): void
    {
        $this->actingAsAdmin();

        [$order] = $this->createOrderWithItem();

        Livewire::test(ViewOrder::class, [
            'record' => $order->getRouteKey(),
        ])
            ->assertOk()
            ->assertSee('Wireless Keyboard');
    }

    public function test_it_edits_an_order(): void
    {
        $this->actingAsAdmin();

        [$order] = $this->createOrderWithItem();

        Livewire::test(EditOrder::class, [
            'record' => $order->getRouteKey(),
        ])
            ->fillForm([
                'notes' => 'Updated by the admin panel',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'notes' => 'Updated by the admin panel',
        ]);
    }

    public function test_it_deletes_an_order_from_the_edit_page(): void
    {
        $this->actingAsAdmin();

        [$order] = $this->createOrderWithItem();

        Livewire::test(EditOrder::class, [
            'record' => $order->getRouteKey(),
        ])
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertSoftDeleted('orders', [
            'id' => $order->id,
        ]);
    }

    /**
     * @return array{0: Order, 1: OrderItem}
     */
    private function createOrderWithItem(
        OrderStatus $status = OrderStatus::PENDING,
    ): array {
        $product = Product::factory()->create([
            'name' => 'Sample Product',
            'stock' => 100,
        ]);

        $order = Order::factory()->create(['status' => $status]);

        $item = OrderItem::factory()
            ->withOrder($order)
            ->withProduct($product)
            ->create([
                'product_name' => 'Wireless Keyboard',
                'sku' => $product->sku,
                'quantity' => 2,
            ]);

        return [$order, $item];
    }

    public function test_an_empty_table_column_and_infolist_entry_read_as_a_dash(): void
    {
        $this->actingAsAdmin();

        // Set once for the panel, so every column and entry in every resource
        // reads a missing value the same way.
        static::assertSame('-', TextColumn::make('anything')->getPlaceholder());
        static::assertSame('-', TextEntry::make('anything')->getPlaceholder());
    }

    public function test_a_money_value_of_nothing_reads_as_a_dash(): void
    {
        $this->actingAsAdmin();

        [$order] = $this->createOrderWithItem();
        $order->update(['tax_amount_in_fils' => 0, 'discount_amount_in_fils' => 0]);

        // An order that was neither taxed nor discounted says so with a dash
        // rather than a row of zeroes.
        $this
            ->get(route('filament.admin.resources.orders.view', ['record' => $order]))
            ->assertOk()
            ->assertDontSee('BHD 0.000');
    }

    public function test_the_order_view_page_reloads_what_it_shows_when_it_is_hydrated(): void
    {
        $this->actingAsAdmin();

        [$order] = $this->createOrderWithItem();

        /** @var ViewOrder $page */
        $page = Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->instance();

        // A later request is handed the record fetched by its key alone, with
        // none of the relations the page shows; the page fills them in again.
        $page->record = Order::query()->findOrFail($order->getKey());

        $page->hydrate();

        $record = $page->record;

        static::assertNotNull($record);
        static::assertTrue($record->relationLoaded('orderItems'));
        static::assertTrue($record->relationLoaded('user'));
        static::assertTrue($record->relationLoaded('shippingAddress'));
        static::assertTrue($record->relationLoaded('billingAddress'));
        static::assertTrue($record->relationLoaded('payments'));
    }

    public function test_an_order_view_page_survives_one_of_its_header_actions(): void
    {
        $this->actingAsAdmin();

        [$order] = $this->createOrderWithItem();

        // The second request re-reads the order, so the page has to load what it
        // shows again rather than only when it is first mounted.
        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction(FulfilOrderAction::class)
            ->assertOk()
            ->assertSee('Wireless Keyboard');
    }

    public function test_the_order_view_page_loads_every_relation_it_shows(): void
    {
        $this->actingAsAdmin();

        [$order] = $this->createOrderWithItem();

        // A real request re-reads the order from the database, so each relation
        // the page shows has to come with it: lazy loading is refused outside
        // production, and one that was not loaded would fail the page.
        $this
            ->get(route('filament.admin.resources.orders.view', ['record' => $order]))
            ->assertOk()
            ->assertSee('Wireless Keyboard');
    }

    public function test_transition_actions_are_only_offered_for_legal_transitions(): void
    {
        $this->actingAsAdmin();

        $pending = Order::factory()->create(['status' => OrderStatus::PENDING]);
        $processing = Order::factory()->create(['status' => OrderStatus::PROCESSING]);
        $shipped = Order::factory()->create(['status' => OrderStatus::SHIPPED]);
        $delivered = Order::factory()->create(['status' => OrderStatus::DELIVERED]);
        $cancelled = Order::factory()->create(['status' => OrderStatus::CANCELLED]);
        $refunded = Order::factory()->create(['status' => OrderStatus::REFUNDED]);

        Livewire::test(ListOrders::class)
            ->assertActionVisible(TestAction::make('fulfil')->table($pending))
            ->assertActionVisible(TestAction::make('cancel')->table($pending))
            ->assertActionVisible(TestAction::make('confirm')->table($pending))
            ->assertActionHidden(TestAction::make('refund')->table($pending))
            ->assertActionHidden(TestAction::make('reinstate')->table($pending))
            ->assertActionVisible(TestAction::make('fulfil')->table($processing))
            ->assertActionVisible(TestAction::make('cancel')->table($processing))
            ->assertActionHidden(TestAction::make('confirm')->table($processing))
            ->assertActionHidden(TestAction::make('fulfil')->table($shipped))
            ->assertActionHidden(TestAction::make('cancel')->table($shipped))
            ->assertActionVisible(TestAction::make('refund')->table($shipped))
            ->assertActionVisible(TestAction::make('refund')->table($delivered))
            ->assertActionHidden(TestAction::make('reinstate')->table($delivered))
            ->assertActionHidden(TestAction::make('cancel')->table($cancelled))
            ->assertActionVisible(TestAction::make('reinstate')->table($cancelled))
            ->assertActionHidden(TestAction::make('confirm')->table($cancelled))
            ->assertActionVisible(TestAction::make('reinstate')->table($refunded))
            ->assertActionHidden(TestAction::make('confirm')->table($refunded))
            ->assertActionHidden(TestAction::make('refund')->table($refunded));
    }

    public function test_fulfilling_an_order_via_the_action_drains_stock_and_reserves(): void
    {
        $this->actingAsAdmin();

        $product = Product::factory()->create([
            'stock' => 100,
            'reserved_stock' => 30,
            'low_stock_threshold' => 5,
        ]);

        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
        ]);

        OrderItem::factory()
            ->withOrder($order)
            ->withProduct($product)
            ->create(['quantity' => 25]);

        // A second order still waiting on the same product is what keeps the
        // shelf held once this one ships: 5 of the 30 the two of them reserve.
        $waiting = Order::factory()->create([
            'status' => OrderStatus::PENDING,
        ]);

        OrderItem::factory()
            ->withOrder($waiting)
            ->withProduct($product)
            ->create(['quantity' => 5]);

        Livewire::test(ListOrders::class)
            ->callAction(TestAction::make('fulfil')->table($order))
            ->assertNotified();

        static::assertSame(OrderStatus::SHIPPED, $order->refresh()->status);
        static::assertSame(75, $product->refresh()->stock);
        static::assertSame(5, $product->refresh()->reserved_stock);
    }

    public function test_cancelling_and_reinstating_an_order_persists_the_status(): void
    {
        $this->actingAsAdmin();

        $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

        Livewire::test(ListOrders::class)
            ->callAction(TestAction::make('cancel')->table($order))
            ->assertNotified();

        static::assertSame(OrderStatus::CANCELLED, $order->refresh()->status);

        Livewire::test(ListOrders::class)
            ->callAction(TestAction::make('reinstate')->table($order))
            ->assertNotified();

        static::assertSame(OrderStatus::PENDING, $order->refresh()->status);
    }

    public function test_refunding_an_order_persists_the_status(): void
    {
        $this->actingAsAdmin();

        $order = Order::factory()->create(['status' => OrderStatus::SHIPPED]);

        Livewire::test(ListOrders::class)
            ->callAction(TestAction::make('refund')->table($order))
            ->assertNotified();

        static::assertSame(OrderStatus::REFUNDED, $order->refresh()->status);
    }

    public function test_collecting_a_pending_payment_through_the_action_settles_it(): void
    {
        $this->actingAsAdmin();

        $order = Order::factory()->create(['status' => OrderStatus::PENDING]);
        $payment = Payment::factory()
            ->for($order)
            ->status(PaymentStatus::PENDING)
            ->create();

        Livewire::test(ListOrders::class)
            ->callAction(TestAction::make('mark_collected')->table($order))
            ->assertNotified();

        static::assertSame(PaymentStatus::PAID, $payment->refresh()->status);
        static::assertNotNull($payment->paid_at);
        static::assertNotNull($order->refresh()->paid_at);
    }

    public function test_the_collect_payment_action_is_only_offered_while_a_payment_is_pending(): void
    {
        $this->actingAsAdmin();

        $awaitingCash = Order::factory()->create(['status' => OrderStatus::PENDING]);
        Payment::factory()
            ->for($awaitingCash)
            ->status(PaymentStatus::PENDING)
            ->create();

        $alreadyPaid = Order::factory()->create(['status' => OrderStatus::PENDING]);
        Payment::factory()->for($alreadyPaid)->paid()->create();

        Livewire::test(ListOrders::class)
            ->assertActionVisible(TestAction::make('mark_collected')->table($awaitingCash))
            ->assertActionHidden(TestAction::make('mark_collected')->table($alreadyPaid));
    }

    public function test_every_order_action_describes_what_it_does_to_the_order(): void
    {
        $actions = [
            'fulfil' => FulfilOrderAction::class,
            'cancel' => CancelOrderAction::class,
            'refund' => RefundOrderAction::class,
            'reinstate' => ReinstateOrderAction::class,
            'confirm' => ConfirmOrderAction::class,
            'mark_collected' => CollectPaymentOrderAction::class,
        ];

        foreach (['en', 'ar'] as $locale) {
            app()->setLocale($locale);

            foreach ($actions as $name => $action) {
                $tooltip = $action::make()->getTooltip();

                static::assertIsString(
                    $tooltip,
                    "The {$name} action has no tooltip.",
                );

                // An unresolved translation key comes back verbatim, so this also
                // catches a tooltip that was added in one language only.
                static::assertNotSame(
                    "filament.order.actions.{$name}_tooltip",
                    $tooltip,
                    "The {$name} tooltip is not translated into {$locale}.",
                );
            }
        }
    }

    public function test_orders_can_be_filtered_by_payment_status(): void
    {
        $this->actingAsAdmin();

        $paidOrder = Order::factory()->create(['status' => OrderStatus::PENDING]);
        Payment::factory()->for($paidOrder)->paid()->create();

        $pendingOrder = Order::factory()->create([
            'status' => OrderStatus::PENDING,
        ]);
        Payment::factory()->for($pendingOrder)->create([
            'status' => PaymentStatus::PENDING,
        ]);

        Livewire::test(ListOrders::class)
            ->filterTable('payment_status', PaymentStatus::PAID->value)
            ->assertCanSeeTableRecords([$paidOrder])
            ->assertCanNotSeeTableRecords([$pendingOrder]);
    }

    public function test_orders_can_be_filtered_by_delivery_method(): void
    {
        $this->actingAsAdmin();

        $pickup = Order::factory()->pickup()->create();
        $standard = Order::factory()->create([
            'delivery_method' => DeliveryMethod::STANDARD,
        ]);

        Livewire::test(ListOrders::class)
            ->filterTable('delivery_method', DeliveryMethod::PICKUP->value)
            ->assertCanSeeTableRecords([$pickup])
            ->assertCanNotSeeTableRecords([$standard]);
    }

    public function test_orders_can_be_filtered_by_more_than_one_status(): void
    {
        $this->actingAsAdmin();

        $pending = Order::factory()->create(['status' => OrderStatus::PENDING]);
        $processing = Order::factory()->create([
            'status' => OrderStatus::PROCESSING,
        ]);
        $delivered = Order::factory()->create([
            'status' => OrderStatus::DELIVERED,
        ]);

        Livewire::test(ListOrders::class)
            ->filterTable('status', [
                OrderStatus::PENDING->value,
                OrderStatus::PROCESSING->value,
            ])
            ->assertCanSeeTableRecords([$pending, $processing])
            ->assertCanNotSeeTableRecords([$delivered]);
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    }
}
