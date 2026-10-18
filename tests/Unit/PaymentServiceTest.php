<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use DomainException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_settle_marks_the_pending_payment_paid_and_stamps_the_order(): void
    {
        $order = Order::factory()->create(['paid_at' => null]);

        $payment = Payment::factory()->for($order)->create([
            'status' => PaymentStatus::PENDING,
            'paid_at' => null,
        ]);

        $settled = app(PaymentService::class)->settle($order);

        static::assertTrue($settled->is($payment));
        static::assertSame(PaymentStatus::PAID, $settled->refresh()->status);
        static::assertNotNull($settled->refresh()->paid_at);
        static::assertNotNull($order->refresh()->paid_at);
    }

    public function test_settle_refuses_an_order_without_a_pending_payment(): void
    {
        $order = Order::factory()->create(['paid_at' => null]);

        Payment::factory()->for($order)->paid()->create();

        $this->expectException(DomainException::class);

        app(PaymentService::class)->settle($order);
    }

    public function test_settle_settles_the_newest_pending_payment(): void
    {
        $order = Order::factory()->create(['paid_at' => null]);

        $older = Payment::factory()->for($order)->create([
            'status' => PaymentStatus::PENDING,
        ]);
        $newer = Payment::factory()->for($order)->create([
            'status' => PaymentStatus::PENDING,
        ]);

        $settled = app(PaymentService::class)->settle($order);

        static::assertTrue($settled->is($newer));
        static::assertFalse($settled->is($older));
    }
}
