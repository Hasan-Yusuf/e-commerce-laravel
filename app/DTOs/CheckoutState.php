<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\CheckoutStep;
use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use Illuminate\Support\Str;

final readonly class CheckoutState
{
    public CheckoutStep $step;

    public string $token;

    /**
     * The steps this checkout asks for, derived from how the order reaches the
     * shopper.
     */
    public CheckoutStepPlan $plan;

    public function __construct(
        ?CheckoutStep $step = null,
        public int $progress = 0,
        public CustomerData $customer = new CustomerData,
        public ShippingAddressData $shippingAddress = new ShippingAddressData,
        public DeliveryMethod $deliveryMethod = DeliveryMethod::STANDARD,
        public PaymentMethod $paymentMethod = PaymentMethod::COD,
        public ?AppliedCoupon $coupon = null,
        #[\SensitiveParameter]
        ?string $token = null,
    ) {
        $this->step = $step ?? CheckoutStep::firstStep();
        $this->token = $token ?? (string) Str::uuid();
        $this->plan = new CheckoutStepPlan($this->deliveryMethod);
    }

    public static function fromArray(mixed $data): self
    {
        /** @var array<string, mixed> $data */
        $data = is_array($data) ? $data : [];

        return new self(
            step: CheckoutStep::tryFrom((string) ($data['step'] ?? '')) ?? CheckoutStep::firstStep(),
            progress: max(
                0,
                min(
                    (int) ($data['progress'] ?? 0),
                    \count(CheckoutStep::formSteps()),
                ),
            ),
            customer: CustomerData::fromArray(
                $data[CheckoutStep::CUSTOMER->value] ?? [],
            ),
            shippingAddress: ShippingAddressData::fromArray(
                $data[CheckoutStep::SHIPPING_ADDRESS->value] ?? [],
            ),
            deliveryMethod: DeliveryMethod::tryFrom(
                (string) data_get(
                    $data,
                    CheckoutStep::DELIVERY->value.'.method',
                ),
            ) ?? DeliveryMethod::STANDARD,
            paymentMethod: PaymentMethod::tryFrom(
                (string) data_get(
                    $data,
                    CheckoutStep::PAYMENT->value.'.method',
                ),
            ) ?? PaymentMethod::COD,
            coupon: AppliedCoupon::fromArray($data['coupon'] ?? null),
            token: self::token($data),
        );
    }

    /**
     * The stored shape: the session's `checkout` key and, verbatim, the draft's
     * `state` column.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'step' => $this->step->value,
            'progress' => $this->progress,
            'token' => $this->token,

            CheckoutStep::CUSTOMER->value => $this->customer->toArray(),
            CheckoutStep::SHIPPING_ADDRESS->value => $this->shippingAddress->toArray(),

            CheckoutStep::DELIVERY->value => [
                'method' => $this->deliveryMethod->value,
            ],
            CheckoutStep::PAYMENT->value => [
                'method' => $this->paymentMethod->value,
            ],

            'coupon' => $this->coupon?->toArray(),
        ];
    }

    public function withStep(CheckoutStep $step): self
    {
        return $this->copy(['step' => $step]);
    }

    public function withProgress(int $progress): self
    {
        return $this->copy(['progress' => $progress]);
    }

    public function withCustomer(CustomerData $customer): self
    {
        return $this->copy(['customer' => $customer]);
    }

    public function withShippingAddress(ShippingAddressData $address): self
    {
        return $this->copy(['shippingAddress' => $address]);
    }

    public function withDeliveryMethod(DeliveryMethod $method): self
    {
        // The method decides whether the address step is part of the flow, so a
        // change of method re-counts the progress against the new flow: the
        // steps already answered stay answered, and the count keeps pointing at
        // the last of them.
        $answered = \array_slice($this->plan->required, 0, $this->progress);
        $next = $this->copy(['deliveryMethod' => $method]);

        return $next->withProgress($next->plan->countAnswered($answered));
    }

    public function withPaymentMethod(PaymentMethod $method): self
    {
        return $this->copy(['paymentMethod' => $method]);
    }

    public function withCoupon(?AppliedCoupon $coupon): self
    {
        return $this->copy(['coupon' => $coupon]);
    }

    /**
     * A copy of this state with the given properties replaced — the one place
     * that has to know every property a copy carries.
     *
     * @param  array<string, mixed>  $changes
     */
    private function copy(array $changes): self
    {
        return new self(
            ...array_merge(
                [
                    'step' => $this->step,
                    'progress' => $this->progress,
                    'customer' => $this->customer,
                    'shippingAddress' => $this->shippingAddress,
                    'deliveryMethod' => $this->deliveryMethod,
                    'paymentMethod' => $this->paymentMethod,
                    'coupon' => $this->coupon,
                    'token' => $this->token,
                ],
                $changes,
            ),
        );
    }

    /**
     * The token a stored state carries back, or a fresh one: a state written
     * before the token existed (or by a tampered one) gets an identity of its
     * own rather than none at all.
     *
     * @param  array<string, mixed>  $data
     */
    private static function token(array $data): string
    {
        $token = (string) ($data['token'] ?? '');

        return $token === '' ? (string) Str::uuid() : $token;
    }
}
