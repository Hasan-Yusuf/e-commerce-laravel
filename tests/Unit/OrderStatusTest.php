<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

/**
 * The legal moves between order statuses — the one table the panel's actions
 * and every order pipeline read.
 */
class OrderStatusTest extends TestCase
{
    public function test_the_legal_moves_are_one_matrix(): void
    {
        $legal = [
            [OrderStatus::PENDING,    OrderStatus::PROCESSING],
            [OrderStatus::PENDING,    OrderStatus::SHIPPED],
            [OrderStatus::PENDING,    OrderStatus::DELIVERED],
            [OrderStatus::PENDING,    OrderStatus::CANCELLED],
            [OrderStatus::PROCESSING, OrderStatus::SHIPPED],
            [OrderStatus::PROCESSING, OrderStatus::DELIVERED],
            [OrderStatus::PROCESSING, OrderStatus::CANCELLED],
            [OrderStatus::SHIPPED,    OrderStatus::REFUNDED],
            [OrderStatus::DELIVERED,  OrderStatus::REFUNDED],
            [OrderStatus::CANCELLED,  OrderStatus::PENDING],
            [OrderStatus::REFUNDED,   OrderStatus::PENDING],
        ];

        foreach ($legal as [$from, $to]) {
            static::assertTrue(
                $from->canTransitionTo($to),
                "{$from->value} → {$to->value} should be legal.",
            );
        }

        // A few of the moves that are not: an order never re-enters itself,
        // nothing goes back out once it has been refunded, a called-off order
        // is only taken back up as a pending one, and the panel's statuses are
        // not a ladder to walk up.
        $illegal = [
            [OrderStatus::PENDING,   OrderStatus::REFUNDED],
            [OrderStatus::SHIPPED,   OrderStatus::PENDING],
            [OrderStatus::SHIPPED,   OrderStatus::DELIVERED],
            [OrderStatus::DELIVERED, OrderStatus::PENDING],
            [OrderStatus::REFUNDED,  OrderStatus::SHIPPED],
            [OrderStatus::CANCELLED, OrderStatus::PROCESSING],
            [OrderStatus::REFUNDED,  OrderStatus::PROCESSING],
            [OrderStatus::CANCELLED, OrderStatus::REFUNDED],
        ];

        foreach ($illegal as [$from, $to]) {
            static::assertFalse(
                $from->canTransitionTo($to),
                "{$from->value} → {$to->value} should not be legal.",
            );
        }
    }

    public function test_no_status_moves_to_itself(): void
    {
        foreach (OrderStatus::cases() as $status) {
            static::assertFalse($status->canTransitionTo($status));
        }
    }

    public function test_the_default_status_is_pending(): void
    {
        static::assertSame(OrderStatus::PENDING, OrderStatus::getDefaultStatus());
    }
}
