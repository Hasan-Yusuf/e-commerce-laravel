<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\CustomerData;
use App\DTOs\ShippingAddressData;
use App\Enums\AddressType;
use App\Enums\CheckoutStep;
use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use App\Models\CheckoutDraft;
use App\Models\Country;
use App\Models\Coupon;
use App\Models\Phone;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Checkout\CheckoutStateStore;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CheckoutServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string SESSION_ID = 'CheckoutUnitSession012345678901234567890';

    private CheckoutService $checkout;

    private CartService $cartService;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        session()->setId(self::SESSION_ID);

        $this->cartService = app(CartService::class);
        $this->checkout = app(CheckoutService::class);
    }

    public function test_start_checkout_seeds_the_first_step_and_the_offered_methods(): void
    {
        $this->checkout->startCheckout();

        $state = $this->checkout->state();

        static::assertSame(CheckoutStep::CUSTOMER, $state->step);
        static::assertSame(0, $state->progress);
        static::assertFalse($state->plan->isComplete($state->progress));
        static::assertSame(DeliveryMethod::STANDARD, $state->deliveryMethod);
        static::assertSame(PaymentMethod::COD, $state->paymentMethod);
    }

    public function test_start_checkout_prefills_what_the_account_already_knows(): void
    {
        $account = $this->accountWithDetails();

        $this->actingAs($account['user']);

        $this->checkout->startCheckout();

        $state = $this->checkout->state();

        static::assertSame('Layla Hassan', $state->customer->name);
        static::assertSame('layla@example.com', $state->customer->email);
        static::assertSame('39000000', $state->customer->phone);
        // The number comes with the country it was given for, so the step is
        // prefilled with a pairing the rule can check.
        static::assertTrue(
            $state->customer->country->is($account['country']),
        );
        static::assertSame('+973 39000000', $state->customer->fullPhone());
        static::assertSame(
            '12 Road 1234',
            $state->shippingAddress->streetAddress,
        );
        static::assertSame('Manama', $state->shippingAddress->city);
        static::assertSame('1234', $state->shippingAddress->postalCode);
        static::assertTrue(
            $state->shippingAddress->country->is($account['country']),
        );
    }

    public function test_start_checkout_fills_the_account_details_into_a_step_the_shopper_left_open(): void
    {
        $account = $this->accountWithDetails();

        // A guest opens the checkout, so the step is blank...
        $this->checkout->startCheckout();
        static::assertSame('', $this->checkout->state()->customer->name);

        // ...and the account's details are filled in once they sign in.
        $this->actingAs($account['user']);
        $this->checkout->startCheckout();

        $state = $this->checkout->state();

        static::assertSame('Layla Hassan', $state->customer->name);
        static::assertSame('39000000', $state->customer->phone);
        static::assertSame(
            '12 Road 1234',
            $state->shippingAddress->streetAddress,
        );
    }

    public function test_start_checkout_keeps_the_answers_the_shopper_gave_themselves(): void
    {
        $account = $this->accountWithDetails();

        $this->checkout->saveCustomer(
            new CustomerData(
                name: 'Ada Lovelace',
                email: 'ada@example.com',
                phone: '39999999',
            ),
        );

        $this->actingAs($account['user']);
        $this->checkout->startCheckout();

        $customer = $this->checkout->state()->customer;

        static::assertSame('Ada Lovelace', $customer->name);
        static::assertSame('ada@example.com', $customer->email);
        static::assertSame('39999999', $customer->phone);
    }

    public function test_saving_the_customer_step_records_the_answer_and_advances(): void
    {
        $this->checkout->saveCustomer(new CustomerData(name: 'Ada Lovelace'));

        $state = $this->checkout->state();

        static::assertSame('Ada Lovelace', $state->customer->name);
        static::assertSame(1, $state->progress);
        static::assertSame(CheckoutStep::DELIVERY, $state->step);
    }

    public function test_saving_an_answered_step_returns_to_the_review(): void
    {
        $this->walkToReview();

        $this->checkout->saveCustomer(new CustomerData(name: 'Grace Hopper'));

        $state = $this->checkout->state();

        static::assertSame(CheckoutStep::REVIEW, $state->step);
        static::assertSame(4, $state->progress);
        static::assertSame('Grace Hopper', $state->customer->name);
    }

    public function test_show_step_cannot_skip_past_the_answers_given(): void
    {
        $this->checkout->startCheckout();

        $this->checkout->showStep(CheckoutStep::PAYMENT);
        static::assertSame(
            CheckoutStep::CUSTOMER,
            $this->checkout->state()->step,
        );

        $this->checkout->saveCustomer(new CustomerData(name: 'Ada Lovelace'));

        $this->checkout->showStep(CheckoutStep::PAYMENT);
        static::assertSame(
            CheckoutStep::DELIVERY,
            $this->checkout->state()->step,
        );
    }

    public function test_show_step_revisits_an_answered_step(): void
    {
        $this->walkToReview();

        $this->checkout->showStep(CheckoutStep::SHIPPING_ADDRESS);
        static::assertSame(
            CheckoutStep::SHIPPING_ADDRESS,
            $this->checkout->state()->step,
        );

        $this->checkout->showStep(CheckoutStep::REVIEW);
        static::assertSame(CheckoutStep::REVIEW, $this->checkout->state()->step);
    }

    public function test_steps_report_which_are_answered_and_which_is_being_shown(): void
    {
        $this->checkout->saveCustomer(new CustomerData);
        $this->checkout->saveDeliveryMethod(DeliveryMethod::STANDARD);

        static::assertSame(
            [
                [
                    'step' => CheckoutStep::CUSTOMER,
                    'state' => 'complete',
                    'number' => 1,
                ],
                [
                    'step' => CheckoutStep::DELIVERY,
                    'state' => 'complete',
                    'number' => 2,
                ],
                [
                    'step' => CheckoutStep::SHIPPING_ADDRESS,
                    'state' => 'current',
                    'number' => 3,
                ],
                [
                    'step' => CheckoutStep::PAYMENT,
                    'state' => 'pending',
                    'number' => 4,
                ],
                [
                    'step' => CheckoutStep::REVIEW,
                    'state' => 'pending',
                    'number' => 5,
                ],
            ],
            $this->checkout->steps(),
        );
    }

    public function test_summary_holds_the_answers_the_cart_and_the_resolved_country(): void
    {
        $country = Country::factory()->bahrain()->create();
        $product = Product::factory()
            ->active()
            ->create([
                'price_in_fils' => 10_000,
                'stock' => 5,
                'reserved_stock' => 0,
            ]);

        $this->cartService->add($product, 2);

        $this->checkout->saveCustomer(
            new CustomerData(
                name: 'Ada Lovelace',
                email: 'ada@example.com',
                phone: '39000000',
            ),
        );
        $this->checkout->saveShippingAddress(
            new ShippingAddressData(
                streetAddress: '12 Road 1234',
                city: 'Manama',
                state: 'Capital',
                countryId: $country->id,
                postalCode: '1234',
            ),
        );

        $summary = $this->checkout->summary();

        static::assertSame('Ada Lovelace', $summary->customer->name);
        static::assertSame('ada@example.com', $summary->customer->email);
        static::assertSame('39000000', $summary->customer->phone);
        static::assertTrue($summary->shippingAddress->country->is($country));
        static::assertSame(
            ['12 Road 1234', 'Manama, Capital', 'Bahrain 1234'],
            $summary->shippingAddress->lines(),
        );
        static::assertSame(DeliveryMethod::STANDARD, $summary->deliveryMethod);
        static::assertSame(PaymentMethod::COD, $summary->paymentMethod);
        static::assertSame(20_000, $summary->cart->subtotalInFils);
    }

    public function test_summary_falls_back_to_the_methods_that_still_exist(): void
    {
        // A checkout started before a method was withdrawn must not break the
        // review: the stored value falls back to the one that is offered.
        session()->put('checkout', [
            'step' => CheckoutStep::REVIEW->value,
            'progress' => 4,
            'delivery' => ['method' => 'teleport'],
            'payment' => ['method' => 'card'],
        ]);

        $summary = $this->checkout->summary();

        static::assertSame(DeliveryMethod::STANDARD, $summary->deliveryMethod);
        static::assertSame(PaymentMethod::COD, $summary->paymentMethod);
    }

    public function test_summary_reads_an_empty_state_as_a_blank_checkout(): void
    {
        $summary = $this->checkout->summary();

        static::assertSame('', $summary->customer->name);
        static::assertSame('', $summary->shippingAddress->streetAddress);
        static::assertSame([], $summary->shippingAddress->lines());
        static::assertSame(DeliveryMethod::STANDARD, $summary->deliveryMethod);
        static::assertSame(PaymentMethod::COD, $summary->paymentMethod);
    }

    public function test_applying_a_coupon_stores_it_and_folds_it_into_the_summary(): void
    {
        $this->cartService->add(
            Product::factory()
                ->active()
                ->create([
                    'price_in_fils' => 20_000,
                    'stock' => 5,
                    'reserved_stock' => 0,
                ]),
            2,
        );

        Coupon::factory()->percentage(10)->create(['code' => 'SAVE10']);

        $result = $this->checkout->applyCoupon('save10');

        static::assertTrue($result->isValid);

        $summary = $this->checkout->summary();

        static::assertSame('SAVE10', $summary->cart->coupon?->code);
        static::assertSame(4_000, $summary->cart->discountInFils);
        static::assertSame(36_000, $summary->cart->totalInFils());
    }

    public function test_applying_an_unknown_coupon_reports_the_reason_and_stores_nothing(): void
    {
        $this->cartService->add(
            Product::factory()
                ->active()
                ->create([
                    'price_in_fils' => 20_000,
                    'stock' => 5,
                    'reserved_stock' => 0,
                ]),
            1,
        );

        $result = $this->checkout->applyCoupon('NOPE');

        static::assertFalse($result->isValid);
        static::assertSame('checkout.coupon.invalid', $result->errorKey);
        static::assertNull($this->checkout->summary()->cart->coupon);
    }

    public function test_removing_a_coupon_restores_the_full_price(): void
    {
        $this->cartService->add(
            Product::factory()
                ->active()
                ->create([
                    'price_in_fils' => 20_000,
                    'stock' => 5,
                    'reserved_stock' => 0,
                ]),
            2,
        );

        Coupon::factory()->percentage(10)->create(['code' => 'SAVE10']);

        $this->checkout->applyCoupon('SAVE10');
        $this->checkout->removeCoupon();

        $summary = $this->checkout->summary();

        static::assertNull($summary->cart->coupon);
        static::assertSame(0, $summary->cart->discountInFils);
        static::assertSame(40_000, $summary->cart->totalInFils());
    }

    public function test_has_cart_items_reflects_the_cart(): void
    {
        static::assertFalse($this->checkout->hasCartItems());

        $product = Product::factory()
            ->active()
            ->create([
                'stock' => 5,
                'reserved_stock' => 0,
            ]);

        $this->cartService->add($product, 1);

        static::assertTrue($this->checkout->hasCartItems());
    }

    public function test_reset_forgets_the_checkout(): void
    {
        $this->checkout->saveCustomer(new CustomerData(name: 'Ada Lovelace'));

        $this->checkout->reset();

        static::assertSame(0, $this->checkout->state()->progress);
        static::assertSame('', $this->checkout->state()->customer->name);
    }

    public function test_a_signed_in_shoppers_answers_are_kept_on_their_account(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->checkout->saveCustomer(new CustomerData(name: 'Layla Hassan'));

        $draft = CheckoutDraft::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        static::assertSame(1, $draft->state['progress']);
        static::assertSame(
            'Layla Hassan',
            $draft->state[CheckoutStep::CUSTOMER->value]['name'],
        );
    }

    public function test_a_signed_in_shopper_picks_up_the_draft_they_left_behind(): void
    {
        $user = User::factory()->create();

        CheckoutDraft::factory()->forUser($user)->create();

        $this->actingAs($user);

        // The session is empty — the draft only exists on the account now.
        $this->checkout->startCheckout();

        $state = $this->checkout->state();

        static::assertSame(1, $state->progress);
        static::assertSame(CheckoutStep::DELIVERY, $state->step);
        static::assertSame('Ada Lovelace', $state->customer->name);
    }

    public function test_a_draft_older_than_the_cart_is_ignored(): void
    {
        $user = User::factory()->create();

        $this->travelTo(
            now()->subDays(CheckoutStateStore::STORED_LIFETIME_DAYS + 1),
        );
        CheckoutDraft::factory()->forUser($user)->create();
        $this->travelBack();

        $this->actingAs($user);
        $this->checkout->startCheckout();

        $state = $this->checkout->state();

        static::assertSame(0, $state->progress);
        static::assertSame(CheckoutStep::CUSTOMER, $state->step);
    }

    public function test_a_guest_keeps_no_draft_on_any_account(): void
    {
        $this->checkout->saveCustomer(new CustomerData(name: 'Ada Lovelace'));

        static::assertSame(
            'Ada Lovelace',
            $this->checkout->state()->customer->name,
        );
        static::assertSame(0, CheckoutDraft::query()->count());
    }

    public function test_reset_forgets_the_stored_draft(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->checkout->saveCustomer(new CustomerData(name: 'Layla Hassan'));
        static::assertSame(1, CheckoutDraft::query()->count());

        $this->checkout->reset();

        static::assertSame('', $this->checkout->state()->customer->name);
        static::assertSame(0, CheckoutDraft::query()->count());
    }

    /**
     * An account that already holds the name, email, phone and address the
     * checkout should fill in from.
     *
     * @return array{user: User, country: Country}
     */
    private function accountWithDetails(): array
    {
        $country = Country::factory()->bahrain()->create();

        $phone = Phone::factory()->create([
            'country_id' => $country->id,
            'phone_number' => '39000000',
        ]);

        $user = User::factory()->create([
            'name' => 'Layla Hassan',
            'email' => 'layla@example.com',
            'phone_id' => $phone->id,
        ]);

        $user->addresses()->create([
            'address_type' => AddressType::SHIPPING->value,
            'street_address' => '12 Road 1234',
            'city' => 'Manama',
            'state' => 'Capital',
            'country_id' => $country->id,
            'postal_code' => '1234',
            'defaulted_at' => now(),
        ]);

        return ['user' => $user, 'country' => $country];
    }

    /**
     * Answer every step, so the shopper stands on the review.
     */
    private function walkToReview(): void
    {
        $this->checkout->saveCustomer(
            new CustomerData(
                name: 'Ada Lovelace',
                email: 'ada@example.com',
                phone: '39000000',
            ),
        );
        $this->checkout->saveDeliveryMethod(DeliveryMethod::STANDARD);
        $this->checkout->saveShippingAddress(
            new ShippingAddressData(
                streetAddress: '12 Road 1234',
                city: 'Manama',
                state: 'Capital',
                countryId: Country::factory()->create()->id,
                postalCode: '1234',
            ),
        );
        $this->checkout->savePaymentMethod(PaymentMethod::COD);
    }
}
