<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class ComparableTraitTest extends TestCase
{
    public function test_is_returns_true_when_equals(): void
    {
        $enum = OrderStatus::PENDING;
        static::assertTrue($enum->is(OrderStatus::PENDING));

        static::assertTrue(
            $enum->is([OrderStatus::PENDING, OrderStatus::PROCESSING]),
        );
    }

    public function test_is_returns_false_when_not_equals(): void
    {
        $enum = OrderStatus::PENDING;
        static::assertFalse($enum->is(OrderStatus::PROCESSING));

        static::assertFalse(
            $enum->is([OrderStatus::PROCESSING, OrderStatus::REFUNDED]),
        );
    }

    public function test_is_not_returns_true_when_not_equals(): void
    {
        $enum = OrderStatus::PENDING;
        static::assertTrue($enum->isNot(OrderStatus::PROCESSING));

        static::assertTrue(
            $enum->isNot([OrderStatus::PROCESSING, OrderStatus::CANCELLED]),
        );
    }

    public function test_is_not_returns_false_when_equals(): void
    {
        $enum = OrderStatus::PENDING;
        static::assertFalse($enum->isNot(OrderStatus::PENDING));
    }
}
