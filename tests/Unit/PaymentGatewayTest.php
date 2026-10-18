<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Payments\CodGateway;
use App\Services\Payments\PaymentGatewayRegistry;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_registry_offers_cash_on_delivery(): void
    {
        $registry = new PaymentGatewayRegistry;

        static::assertSame([PaymentMethod::COD], $registry->methods()->all());
        static::assertSame(
            [PaymentMethod::COD->value],
            $registry->methodValues(),
        );
        static::assertTrue($registry->has(PaymentMethod::COD));
    }

    public function test_the_registry_resolves_the_driver_for_a_method(): void
    {
        static::assertInstanceOf(
            CodGateway::class,
            (new PaymentGatewayRegistry)->gateway(PaymentMethod::COD),
        );
    }

    public function test_the_registry_refuses_a_method_with_no_driver(): void
    {
        config()->set('payments.gateways', []);

        $this->expectException(InvalidArgumentException::class);

        (new PaymentGatewayRegistry)->gateway(PaymentMethod::COD);
    }

    public function test_the_registry_only_offers_the_configured_drivers(): void
    {
        config()->set('payments.gateways', []);

        $registry = new PaymentGatewayRegistry;

        static::assertSame([], $registry->methods()->all());
        static::assertSame([], $registry->methodValues());
        static::assertFalse($registry->has(PaymentMethod::COD));
    }

    public function test_the_cash_on_delivery_driver_reports_the_method_it_handles(): void
    {
        static::assertSame(PaymentMethod::COD, (new CodGateway)->method());
    }

    public function test_the_cash_on_delivery_driver_records_a_pending_payment_for_the_order_total(): void
    {
        $order = Order::factory()->create([
            'subtotal_in_fils' => 100_000,
            'tax_amount_in_fils' => 5_000,
            'shipping_amount_in_fils' => 0,
            'discount_amount_in_fils' => 0,
        ]);

        $payment = (new CodGateway)->createPaymentFor($order);

        $this->assertModelExists($payment);
        static::assertTrue($payment->order->is($order));
        static::assertSame(PaymentMethod::COD, $payment->method);
        static::assertSame(PaymentStatus::PENDING, $payment->status);
        static::assertSame(105_000, $payment->amount_in_fils->toFils());
        static::assertNull($payment->paid_at);
        static::assertNull($payment->meta);
        static::assertTrue($order->payments()->first()->is($payment));
    }
}
