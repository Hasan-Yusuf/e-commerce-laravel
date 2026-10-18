<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An order is financial history. Removing the account that placed it must not
 * take the order — or its payments — with it.
 */
class OrderRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_customer_does_not_delete_their_orders_or_payments(): void
    {
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER]);
        $order = Order::factory()->for($customer)->create();
        $payment = Payment::factory()->for($order)->create();

        $deleteWasBlocked = false;

        try {
            $customer->delete();
        } catch (QueryException) {
            // The order restricts the delete, which is the point of the test.
            $deleteWasBlocked = true;
        }

        static::assertTrue($deleteWasBlocked);

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id]);

        // And the order's owner is still there to look at it through.
        $this->assertDatabaseHas('users', ['id' => $customer->id]);
    }
}
