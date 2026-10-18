<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\AppliedCoupon;
use App\DTOs\CheckoutState;
use App\DTOs\CustomerData;
use App\DTOs\ShippingAddressData;
use App\Enums\CheckoutStep;
use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use App\Models\Country;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CheckoutStateTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_reads_a_stored_state_back_into_its_value_objects(): void
    {
        $country = Country::factory()->bahrain()->create();

        $state = CheckoutState::fromArray([
            'step' => CheckoutStep::DELIVERY->value,
            'progress' => 2,
            'customer' => [
                'name' => 'Ada Lovelace',
                'email' => 'ada@example.com',
                'phone' => '39000000',
            ],
            'shipping_address' => [
                'street_address' => '12 Road 1234',
                'city' => 'Manama',
                'state' => 'Capital',
                'country_id' => $country->id,
                'postal_code' => '1234',
            ],
            'delivery' => ['method' => DeliveryMethod::STANDARD->value],
            'payment' => ['method' => PaymentMethod::COD->value],
        ]);

        static::assertSame(CheckoutStep::DELIVERY, $state->step);
        static::assertSame(2, $state->progress);
        static::assertSame('Ada Lovelace', $state->customer->name);
        static::assertSame('ada@example.com', $state->customer->email);
        static::assertSame('39000000', $state->customer->phone);
        static::assertSame(
            '12 Road 1234',
            $state->shippingAddress->streetAddress,
        );
        static::assertTrue($state->shippingAddress->country->is($country));
        static::assertSame(DeliveryMethod::STANDARD, $state->deliveryMethod);
        static::assertSame(PaymentMethod::COD, $state->paymentMethod);
    }

    public function test_it_writes_the_state_back_in_the_shape_it_is_stored_as(): void
    {
        $state = new CheckoutState(
            step: CheckoutStep::PAYMENT,
            progress: 3,
            customer: new CustomerData(
                name: 'Ada Lovelace',
                email: 'ada@example.com',
                phone: '39000000',
            ),
            shippingAddress: new ShippingAddressData(
                streetAddress: '12 Road 1234',
                city: 'Manama',
                state: 'Capital',
                countryId: null,
                postalCode: '1234',
            ),
        );

        static::assertSame(
            [
                'step' => CheckoutStep::PAYMENT->value,
                'progress' => 3,
                'token' => $state->token,
                'customer' => [
                    'name' => 'Ada Lovelace',
                    'email' => 'ada@example.com',
                    'phone' => '39000000',
                    'country_id' => null,
                ],
                'shipping_address' => [
                    'street_address' => '12 Road 1234',
                    'city' => 'Manama',
                    'state' => 'Capital',
                    'country_id' => null,
                    'postal_code' => '1234',
                ],
                'delivery' => ['method' => DeliveryMethod::STANDARD->value],
                'payment' => ['method' => PaymentMethod::COD->value],
                'coupon' => null,
            ],
            $state->toArray(),
        );
    }

    public function test_it_carries_an_applied_coupon_through_a_round_trip(): void
    {
        $state = new CheckoutState(
            coupon: new AppliedCoupon(
                id: 7,
                code: 'SAVE10',
                discountInFils: 4_000,
            ),
        );

        $restored = CheckoutState::fromArray($state->toArray());

        $coupon = $restored->coupon;

        static::assertNotNull($coupon);
        static::assertSame(7, $coupon->id);
        static::assertSame('SAVE10', $coupon->code);
        static::assertSame(4_000, $coupon->discountInFils);
    }

    public function test_it_reads_a_coupon_that_is_not_there_as_none(): void
    {
        static::assertNull(CheckoutState::fromArray([])->coupon);
    }

    public function test_it_falls_back_to_the_defaults_for_anything_it_cannot_read(): void
    {
        $state = CheckoutState::fromArray([
            'step' => 'nonsense',
            'progress' => 99,
            'customer' => 'not an array',
            'shipping_address' => 42,
            'delivery' => ['method' => 'teleport'],
            'payment' => ['method' => 'card'],
        ]);

        static::assertSame(CheckoutStep::CUSTOMER, $state->step);
        static::assertSame(4, $state->progress);
        static::assertSame('', $state->customer->name);
        static::assertSame('', $state->shippingAddress->streetAddress);
        static::assertNull($state->shippingAddress->countryId);
        static::assertNull($state->shippingAddress->country);
        static::assertSame(DeliveryMethod::STANDARD, $state->deliveryMethod);
        static::assertSame(PaymentMethod::COD, $state->paymentMethod);
    }

    public function test_reading_nothing_gives_the_blank_flow(): void
    {
        $state = CheckoutState::fromArray(null);

        static::assertSame(CheckoutStep::CUSTOMER, $state->step);
        static::assertSame(0, $state->progress);
        static::assertSame('', $state->customer->email);
        static::assertFalse($state->plan->isComplete($state->progress));
    }

    public function test_the_token_identifies_the_attempt_and_survives_a_round_trip(): void
    {
        $state = new CheckoutState;

        static::assertNotSame('', $state->token);

        // The token the state is stored with reads back with it, so the flow
        // keeps one identity from the first step to the order it places.
        static::assertSame(
            $state->token,
            CheckoutState::fromArray($state->toArray())->token,
        );

        // A state that carries no token — one written before they existed, or
        // a tampered one — gets an identity of its own rather than none.
        static::assertNotSame('', CheckoutState::fromArray(null)->token);
        static::assertNotSame(
            CheckoutState::fromArray(null)->token,
            CheckoutState::fromArray(null)->token,
        );
    }

    public function test_it_reports_which_steps_are_answered(): void
    {
        $state = new CheckoutState(progress: 2);

        static::assertTrue($state->plan->hasAnswered(CheckoutStep::CUSTOMER, $state->progress));
        static::assertTrue($state->plan->hasAnswered(CheckoutStep::DELIVERY, $state->progress));
        static::assertFalse($state->plan->hasAnswered(CheckoutStep::SHIPPING_ADDRESS, $state->progress));
        static::assertFalse($state->plan->hasAnswered(CheckoutStep::PAYMENT, $state->progress));
    }

    public function test_collecting_the_order_skips_the_address_step(): void
    {
        $state = new CheckoutState(deliveryMethod: DeliveryMethod::PICKUP);

        static::assertSame(
            [
                CheckoutStep::CUSTOMER,
                CheckoutStep::DELIVERY,
                CheckoutStep::PAYMENT,
            ],
            $state->plan->required,
        );
        static::assertFalse($state->plan->hasAnswered(CheckoutStep::SHIPPING_ADDRESS, $state->progress));
    }

    public function test_a_lifted_step_leaves_no_gap_in_the_numbers(): void
    {
        $delivering = new CheckoutState;

        static::assertSame(3, $delivering->plan->number(CheckoutStep::SHIPPING_ADDRESS));
        static::assertSame(4, $delivering->plan->number(CheckoutStep::PAYMENT));
        static::assertSame(5, $delivering->plan->number(CheckoutStep::REVIEW));

        // Collecting the order lifts the address, and the steps after it count
        // down into its place rather than skipping its number.
        $collecting = new CheckoutState(deliveryMethod: DeliveryMethod::PICKUP);

        static::assertNull($collecting->plan->number(CheckoutStep::SHIPPING_ADDRESS));
        static::assertSame(3, $collecting->plan->number(CheckoutStep::PAYMENT));
        static::assertSame(4, $collecting->plan->number(CheckoutStep::REVIEW));
    }

    public function test_changing_the_method_re_counts_the_progress(): void
    {
        // The contact, the delivery and the address answered, in that order.
        $state = new CheckoutState(progress: 3);

        // Collecting the order takes the address out of the flow, so the count
        // steps back to the last step both flows share...
        $collecting = $state->withDeliveryMethod(DeliveryMethod::PICKUP);
        static::assertSame(2, $collecting->progress);

        // ...and turning it back into a delivery puts the address in again, so
        // the count lands on it and the step is asked for once more.
        $delivering = $collecting->withDeliveryMethod(DeliveryMethod::STANDARD);
        static::assertSame(2, $delivering->progress);
        static::assertSame(
            CheckoutStep::SHIPPING_ADDRESS,
            $delivering->plan->reachableStep($delivering->progress),
        );
    }

    public function test_the_reachable_step_is_the_one_after_the_last_answer(): void
    {
        $blank = new CheckoutState;
        static::assertSame(
            CheckoutStep::CUSTOMER,
            $blank->plan->reachableStep($blank->progress),
        );

        $two = new CheckoutState(progress: 2);
        static::assertSame(
            CheckoutStep::SHIPPING_ADDRESS,
            $two->plan->reachableStep($two->progress),
        );

        $done = new CheckoutState(progress: 4);
        static::assertSame(
            CheckoutStep::REVIEW,
            $done->plan->reachableStep($done->progress),
        );
    }

    public function test_is_complete_needs_every_form_step(): void
    {
        $partial = new CheckoutState(progress: 3);
        static::assertFalse($partial->plan->isComplete($partial->progress));

        $complete = new CheckoutState(progress: 4);
        static::assertTrue($complete->plan->isComplete($complete->progress));
    }

    public function test_a_copy_replaces_one_property_at_a_time(): void
    {
        $state = new CheckoutState(
            progress: 1,
            customer: new CustomerData(name: 'Ada Lovelace'),
        );

        $copy = $state
            ->withProgress(3)
            ->withDeliveryMethod(DeliveryMethod::STANDARD)
            ->withStep(CheckoutStep::REVIEW);

        static::assertSame('Ada Lovelace', $copy->customer->name);
        static::assertSame(3, $copy->progress);
        static::assertSame(DeliveryMethod::STANDARD, $copy->deliveryMethod);
        static::assertSame(CheckoutStep::REVIEW, $copy->step);

        // The state it was copied from is untouched.
        static::assertSame(1, $state->progress);
        static::assertSame(CheckoutStep::CUSTOMER, $state->step);
    }
}
