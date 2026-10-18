<?php

declare(strict_types=1);

namespace App\Pipelines\Orders\Pipes;

use App\DTOs\OrderProcessingContext;
use App\Services\Payments\PaymentGatewayRegistry;
use Closure;

/**
 * The method the shopper chose names the driver: the registry owns which
 * methods exist, so nothing here names one.
 */
class RecordPayment
{
    public function __construct(
        private PaymentGatewayRegistry $gatewayService,
    ) {}

    public function handle(
        OrderProcessingContext $context,
        Closure $next,
    ): OrderProcessingContext {
        $this->gatewayService
            ->gateway($context->checkoutOrFail()->paymentMethod)
            ->createPaymentFor($context->orderOrFail());

        return $next($context->recordPipe(self::class));
    }
}
