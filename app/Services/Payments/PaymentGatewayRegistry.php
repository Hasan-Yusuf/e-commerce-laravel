<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * The payment methods the storefront offers, and the driver behind each one.
 * Drivers are listed in config/payments.php; the registry resolves them lazily
 * so a driver is only built when a method is actually rendered or charged.
 *
 * The checkout validates a submitted method against `methodValues()` and, once
 * the order exists, asks `gateway()` for the driver that writes the payment —
 * so adding a method is a driver plus a config line, never a checkout change.
 */
class PaymentGatewayRegistry
{
    /**
     * The resolved drivers, keyed by the method value each handles.
     *
     * @var array<string, PaymentGateway>
     */
    private array $gateways = [];

    private bool $resolved = false;

    /**
     * The methods the storefront offers, in the order they are registered.
     *
     * @return Collection<int, PaymentMethod>
     */
    public function methods(): Collection
    {
        $this->resolve();

        return collect(array_keys($this->gateways))->map(
            PaymentMethod::from(...),
        )
            ->values();
    }

    /**
     * The method values, for validating what the payment step submitted.
     *
     * @return array<int, string>
     */
    public function methodValues(): array
    {
        return $this
            ->methods()
            ->map(static fn (PaymentMethod $method): string => $method->value)
            ->all();
    }

    public function has(PaymentMethod $method): bool
    {
        $this->resolve();

        return array_key_exists($method->value, $this->gateways);
    }

    /**
     * The driver that handles the method.
     *
     * @throws InvalidArgumentException when no driver handles it
     */
    public function gateway(PaymentMethod $method): PaymentGateway
    {
        $this->resolve();

        if (! array_key_exists($method->value, $this->gateways)) {
            throw new InvalidArgumentException(
                "No payment gateway is registered for the [{$method->value}] method.",
            );
        }

        return $this->gateways[$method->value];
    }

    private function resolve(): void
    {
        if ($this->resolved) {
            return;
        }

        /** @var array<int, class-string<PaymentGateway>> $drivers */
        $drivers = config('payments.gateways', []);

        foreach ($drivers as $driver) {
            $gateway = app($driver);

            $this->gateways[$gateway->method()->value] = $gateway;
        }

        $this->resolved = true;
    }
}
