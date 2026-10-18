<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AddressType;
use App\Enums\CheckoutStep;
use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\CheckoutDraft;
use App\Models\Country;
use App\Models\Order;
use App\Models\Phone;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * A fixed, valid session id (40 alphanumeric chars) so guest requests within
     * a test share one cart and one set of checkout answers. The test client
     * does not replay the session cookie automatically, so it is sent with every
     * request instead.
     */
    private const string SESSION_ID = 'CheckoutTestSession012345678901234567890';

    /**
     * The country the flow's phone numbers are given for, made once per test.
     */
    private ?Country $bahrain = null;

    public function test_the_cart_summary_leads_to_the_checkout(): void
    {
        $this->addToCart($this->createProduct())->assertRedirect();

        $this
            ->asGuest()
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('href="'.route('checkout.index').'"', false)
            ->assertSee(__('carts.summary.checkout'));
    }

    public function test_an_empty_cart_is_sent_back_to_the_cart(): void
    {
        $this
            ->asGuest()
            ->get(route('checkout.index'))
            ->assertRedirect(route('cart.index'));

        $this
            ->asGuest()
            ->get(CheckoutStep::REVIEW->url())
            ->assertRedirect(route('cart.index'));

        $this
            ->asGuest()
            ->post(route('checkout.customer.store'), [
                'name' => 'Ada Lovelace',
                'email' => 'ada@example.com',
                'country_id' => $this->bahrain()->id,
                'phone_number' => '39000000',
            ])
            ->assertRedirect(route('cart.index'));
    }

    public function test_the_checkout_opens_on_the_customer_step_with_the_totals(): void
    {
        $product = $this->createProduct(['price_in_fils' => 10_000]);
        $this->addToCart($product, quantity: 2);

        $this
            ->asGuest()
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee(__('checkout.steps.customer.title'))
            ->assertSee(__('checkout.steps.label'))
            ->assertSee(
                'action="'.route('checkout.customer.store').'"',
                false,
            )
            // The order summary travels with every step.
            ->assertSee(__('carts.summary.total'))
            ->assertSee('20 BHD')
            ->assertSee($product->name);
    }

    public function test_the_customer_step_stores_the_contact_details_and_moves_on(): void
    {
        $this->addToCart($this->createProduct());

        // Every label on the step is a translated one, the country whose
        // numbering plan the number is read against included: a key the lang
        // file does not hold would print itself here.
        $this
            ->asGuest()
            ->get(CheckoutStep::CUSTOMER->url())
            ->assertOk()
            ->assertSee(__('checkout.fields.country'))
            ->assertDontSee('checkout.fields.', false);

        $this->submitCustomerStep()->assertRedirect(
            CheckoutStep::DELIVERY->url(),
        );

        static::assertSame(
            [
                'name' => 'Ada Lovelace',
                'email' => 'ada@example.com',
                'phone' => '39000000',
                'country_id' => $this->bahrain()->id,
            ],
            session('checkout.'.CheckoutStep::CUSTOMER->value),
        );
        static::assertSame(1, session('checkout.progress'));

        // The flow moved on to the delivery...
        $this
            ->asGuest()
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee(__('checkout.steps.delivery.title'))
            ->assertSee(
                'action="'.route('checkout.delivery.store').'"',
                false,
            );

        // ...and going back to the first step finds the answers filled in.
        $this
            ->asGuest()
            ->get(CheckoutStep::CUSTOMER->url())
            ->assertOk()
            ->assertSee('value="Ada Lovelace"', false)
            ->assertSee('value="ada@example.com"', false);
    }

    public function test_the_customer_step_needs_a_name_and_an_email(): void
    {
        $this->addToCart($this->createProduct());
        $this->openCheckout();

        $this
            ->asGuest()
            ->post(route('checkout.customer.store'), [])
            ->assertSessionHasErrors(['name', 'email']);

        static::assertSame(0, session('checkout.progress'));
        static::assertSame(
            [
                'name' => '',
                'email' => '',
                'phone' => '',
                'country_id' => null,
            ],
            session('checkout.'.CheckoutStep::CUSTOMER->value),
        );
    }

    public function test_the_customer_step_is_answered_without_a_phone_number(): void
    {
        $this->addToCart($this->createProduct());

        // A number is the shopper's to give: leaving all of it out is an answered
        // step like any other.
        $this
            ->asGuest()
            ->post(route('checkout.customer.store'), [
                'name' => 'Ada Lovelace',
                'email' => 'ada@example.com',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(CheckoutStep::DELIVERY->url());

        static::assertSame(1, session('checkout.progress'));
    }

    public function test_a_phone_number_needs_the_country_it_belongs_to(): void
    {
        $this->addToCart($this->createProduct());
        $this->openCheckout();

        // A number without its country cannot be checked against a numbering
        // plan, so the step refuses the pairing rather than the number.
        $this
            ->asGuest()
            ->post(route('checkout.customer.store'), [
                'name' => 'Ada Lovelace',
                'email' => 'ada@example.com',
                'phone_number' => '39000000',
            ])
            ->assertSessionHasErrors('country_id');

        static::assertSame(0, session('checkout.progress'));

        // The other half is refused too: a country with no number is not a
        // phone, so there is nothing to keep.
        $this
            ->asGuest()
            ->post(route('checkout.customer.store'), [
                'name' => 'Ada Lovelace',
                'email' => 'ada@example.com',
                'country_id' => $this->bahrain()->id,
            ])
            ->assertSessionHasErrors('phone_number');

        static::assertSame(0, session('checkout.progress'));

        // With both halves given, the same submission is answered.
        $this->submitCustomerStep()->assertRedirect(
            CheckoutStep::DELIVERY->url(),
        );
    }

    /**
     * @param  array<string, string>  $invalid
     */
    #[DataProvider('invalidCustomerFields')]
    public function test_the_customer_step_rejects_invalid_contact_details(
        array $invalid,
        string $field,
    ): void {
        $this->addToCart($this->createProduct());
        $this->openCheckout();

        $this
            ->asGuest()
            ->post(
                route('checkout.customer.store'),
                array_merge(
                    [
                        'name' => 'Ada Lovelace',
                        'email' => 'ada@example.com',
                        'country_id' => $this->bahrain()->id,
                        'phone_number' => '39000000',
                    ],
                    $invalid,
                ),
            )
            ->assertSessionHasErrors($field);

        static::assertSame(0, session('checkout.progress'));
    }

    /**
     * @return array<string, array<int, array<string, string>|string>>
     */
    public static function invalidCustomerFields(): array
    {
        return [
            'not an email' => [['email' => 'not-an-email'], 'email'],
            'name too long' => [['name' => str_repeat('a', 256)], 'name'],
            'phone too long' => [
                ['phone_number' => str_repeat('1', 33)],
                'phone_number',
            ],
            // A number is only as long as its country's numbering plan says:
            // Bahrain's are eight digits (countries.phone_length).
            'phone too short for its country' => [
                ['phone_number' => '3900'],
                'phone_number',
            ],
            'phone not digits' => [
                ['phone_number' => '+973 39000000'],
                'phone_number',
            ],
        ];
    }

    public function test_the_shipping_step_stores_the_address_and_moves_on(): void
    {
        $this->addToCart($this->createProduct());
        $this->submitCustomerStep();
        $this->submitDeliveryStep();

        $address = $this->address();

        $this
            ->asGuest()
            ->post(route('checkout.shipping.store'), $address)
            ->assertRedirect(CheckoutStep::PAYMENT->url());

        static::assertSame(
            $address,
            session('checkout.'.CheckoutStep::SHIPPING_ADDRESS->value),
        );
        static::assertSame(3, session('checkout.progress'));

        // The flow moved on to the payment step, and the address is filled in
        // again when the shopper goes back to it.
        $this
            ->asGuest()
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee(__('checkout.steps.payment.title'));

        $this
            ->asGuest()
            ->get(CheckoutStep::SHIPPING_ADDRESS->url())
            ->assertOk()
            ->assertSee('value="Manama"', false)
            ->assertSee('value="Capital"', false);
    }

    public function test_the_shipping_step_needs_a_complete_address(): void
    {
        $this->addToCart($this->createProduct());
        $this->submitCustomerStep();
        $this->submitDeliveryStep();

        $this
            ->asGuest()
            ->post(route('checkout.shipping.store'), [])
            ->assertSessionHasErrors([
                'street_address',
                'city',
                'state',
                'country_id',
                'postal_code',
            ]);

        static::assertSame(2, session('checkout.progress'));
    }

    public function test_the_shipping_step_rejects_an_unknown_country(): void
    {
        $this->addToCart($this->createProduct());
        $this->submitCustomerStep();
        $this->submitDeliveryStep();

        $this
            ->asGuest()
            ->post(
                route('checkout.shipping.store'),
                $this->address(['country_id' => 9_999_999]),
            )
            ->assertSessionHasErrors('country_id');

        static::assertSame(2, session('checkout.progress'));
    }

    public function test_the_shipping_address_is_kept_on_the_account_when_asked(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->addToCart($this->createProduct());
        $this->submitCustomerStep();
        $this->submitDeliveryStep();

        $address = $this->address(['save_to_account' => '1']);

        $this
            ->asGuest()
            ->post(route('checkout.shipping.store'), $address)
            ->assertRedirect(CheckoutStep::PAYMENT->url());

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'street_address' => '12 Road 1234',
            'city' => 'Manama',
            'address_type' => 'shipping',
        ]);

        // Answering the step again with the same address does not duplicate it.
        $this->asGuest()->post(route('checkout.shipping.store'), $address);

        static::assertSame(1, $user->addresses()->count());
    }

    public function test_the_shipping_address_is_not_kept_on_the_account_unless_asked(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->addToCart($this->createProduct());
        $this->submitCustomerStep();
        $this->submitDeliveryStep();

        $this->asGuest()->post(
            route('checkout.shipping.store'),
            $this->address(),
        );

        static::assertSame(0, Address::query()->count());
    }

    public function test_the_delivery_step_stores_the_chosen_method(): void
    {
        $this->addToCart($this->createProduct());
        $this->submitCustomerStep();

        $this
            ->asGuest()
            ->post(route('checkout.delivery.store'), [
                'delivery_method' => DeliveryMethod::STANDARD->value,
            ])
            ->assertRedirect(CheckoutStep::SHIPPING_ADDRESS->url());

        static::assertSame(
            ['method' => DeliveryMethod::STANDARD->value],
            session('checkout.'.CheckoutStep::DELIVERY->value),
        );
        static::assertSame(2, session('checkout.progress'));
    }

    public function test_the_delivery_step_offers_a_collection_at_the_store(): void
    {
        $this->addToCart($this->createProduct());
        $this->submitCustomerStep();

        $this
            ->asGuest()
            ->get(CheckoutStep::DELIVERY->url())
            ->assertOk()
            // Both ways to get the order are offered, each with its own line.
            ->assertSee(DeliveryMethod::STANDARD->getLabel())
            ->assertSee(DeliveryMethod::PICKUP->getLabel())
            ->assertSee(DeliveryMethod::PICKUP->description());

        // Collecting it from the store lifts the address step: the flow goes
        // straight on to payment.
        $this
            ->asGuest()
            ->post(route('checkout.delivery.store'), [
                'delivery_method' => DeliveryMethod::PICKUP->value,
            ])
            ->assertRedirect(CheckoutStep::PAYMENT->url());

        static::assertSame(
            ['method' => DeliveryMethod::PICKUP->value],
            session('checkout.'.CheckoutStep::DELIVERY->value),
        );
    }

    public function test_the_delivery_step_rejects_an_unknown_method(): void
    {
        $this->addToCart($this->createProduct());
        $this->submitCustomerStep();

        $this
            ->asGuest()
            ->post(route('checkout.delivery.store'), [
                'delivery_method' => 'teleport',
            ])
            ->assertSessionHasErrors('delivery_method');

        static::assertSame(1, session('checkout.progress'));
    }

    public function test_collecting_the_order_skips_the_address_step(): void
    {
        $this->addToCart($this->createProduct());
        $this->submitCustomerStep();

        // Choosing to collect the order from the shop goes straight past the
        // address, which has nowhere to deliver to...
        $this
            ->asGuest()
            ->post(route('checkout.delivery.store'), [
                'delivery_method' => DeliveryMethod::PICKUP->value,
            ])
            ->assertRedirect(CheckoutStep::PAYMENT->url());

        static::assertSame(2, session('checkout.progress'));

        // ...so asking for it lands on the step the flow has actually reached.
        $this
            ->asGuest()
            ->get(CheckoutStep::SHIPPING_ADDRESS->url())
            ->assertRedirect(CheckoutStep::PAYMENT->url());

        // The stepper leaves the skipped step out of the flow it draws.
        $this
            ->asGuest()
            ->get(CheckoutStep::PAYMENT->url())
            ->assertOk()
            ->assertDontSee(__('checkout.steps.shipping_address.title'))
            // The Back button backs onto the delivery, not the address.
            ->assertSee('href="'.CheckoutStep::DELIVERY->url().'"', false);
    }

    public function test_a_collection_order_is_placed_without_an_address(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->addToCart($this->createProduct());
        $this->submitCustomerStep();
        $this->submitDeliveryStep([
            'delivery_method' => DeliveryMethod::PICKUP->value,
        ]);
        $this->submitPaymentStep()->assertRedirect(CheckoutStep::REVIEW->url());

        // The review has no address to restate for a collection.
        $this
            ->asGuest()
            ->get(CheckoutStep::REVIEW->url())
            ->assertOk()
            ->assertDontSee(__('checkout.steps.shipping_address.title'));

        $this
            ->asGuest()
            ->post(route('checkout.place'), [
                'checkout_token' => session('checkout.token'),
            ])
            ->assertRedirect();

        $order = Order::query()->firstOrFail();

        static::assertSame(DeliveryMethod::PICKUP, $order->delivery_method);
        static::assertNull($order->shipping_address_id);
        static::assertNull($order->billing_address_id);
        static::assertSame(0, Address::query()->count());
    }

    public function test_the_payment_step_stores_cash_on_delivery(): void
    {
        $this->addToCart($this->createProduct());
        $this->walkToPayment();

        $this
            ->asGuest()
            ->post(route('checkout.payment.store'), [
                'payment_method' => PaymentMethod::COD->value,
            ])
            ->assertRedirect(CheckoutStep::REVIEW->url());

        static::assertSame(
            ['method' => PaymentMethod::COD->value],
            session('checkout.'.CheckoutStep::PAYMENT->value),
        );
        static::assertSame(4, session('checkout.progress'));
    }

    public function test_the_payment_step_rejects_a_method_with_no_gateway(): void
    {
        $this->addToCart($this->createProduct());
        $this->walkToPayment();

        $this
            ->asGuest()
            ->post(route('checkout.payment.store'), [
                'payment_method' => 'card',
            ])
            ->assertSessionHasErrors('payment_method');

        static::assertSame(3, session('checkout.progress'));
    }

    public function test_cash_on_delivery_is_the_only_payment_method_offered(): void
    {
        $this->addToCart($this->createProduct());
        $this->walkToPayment();

        $response = $this
            ->asGuest()
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee(__('enums.payment_method.cod'))
            ->assertSee(__('checkout.payment.methods.cod.description'));

        static::assertSame(
            1,
            substr_count($response->getContent(), 'name="payment_method"'),
        );
    }

    public function test_the_review_shows_every_answer_and_the_totals(): void
    {
        $product = $this->createProduct([
            'price_in_fils' => 10_000,
            'name' => 'ThinkPad X1',
        ]);
        $this->addToCart($product, quantity: 2);
        $this->submitCustomerStep();
        $this->submitDeliveryStep();
        $this->submitShippingStep();
        $this->submitPaymentStep()->assertRedirect(CheckoutStep::REVIEW->url());

        $this
            ->asGuest()
            ->get(CheckoutStep::REVIEW->url())
            ->assertOk()
            ->assertSee('ThinkPad X1')
            ->assertSee(__('checkout.review.items'))
            ->assertSee(__('checkout.steps.customer.title'))
            ->assertSee('Ada Lovelace')
            ->assertSee('ada@example.com')
            ->assertSee('12 Road 1234')
            ->assertSee('Manama')
            ->assertSee('Bahrain')
            ->assertSee(DeliveryMethod::STANDARD->getLabel())
            ->assertSee(PaymentMethod::COD->getLabel())
            ->assertSee(__('checkout.payment.methods.cod.description'))
            ->assertSee('20 BHD')
            ->assertSee(__('checkout.place.action'))
            // Every section is a card of its own, each ending in the button that
            // edits what it shows; the items span the row while the answers pair
            // up beside each other.
            ->assertSee('card review-block', false)
            ->assertSee('review-block--wide', false)
            ->assertSee(
                __('checkout.actions.edit', [
                    'subject' => __('checkout.review.items'),
                ]),
            )
            ->assertSee(
                __('checkout.actions.edit', [
                    'subject' => CheckoutStep::CUSTOMER->label(),
                ]),
            );
    }

    public function test_a_step_ahead_of_the_answers_cannot_be_shown(): void
    {
        $this->addToCart($this->createProduct());

        // A form step past the one that is open, and the review itself, both
        // land on the step the shopper should be on.
        $this
            ->asGuest()
            ->get(CheckoutStep::PAYMENT->url())
            ->assertRedirect(CheckoutStep::CUSTOMER->url());

        $this
            ->asGuest()
            ->get(CheckoutStep::REVIEW->url())
            ->assertRedirect(CheckoutStep::CUSTOMER->url());

        // Following that redirect shows the customer step, not the one asked for.
        $this
            ->asGuest()
            ->get(CheckoutStep::CUSTOMER->url())
            ->assertOk()
            ->assertSee(__('checkout.steps.customer.title'))
            ->assertSee(
                'action="'.route('checkout.customer.store').'"',
                false,
            )
            ->assertDontSee('action="'.route('checkout.place').'"', false);
    }

    public function test_an_unknown_step_is_not_found(): void
    {
        $this->addToCart($this->createProduct());

        $this
            ->asGuest()
            ->get(route('checkout.step', ['step' => 'nonsense']))
            ->assertNotFound();
    }

    public function test_the_stepper_marks_the_answered_steps_and_the_one_being_shown(): void
    {
        $this->addToCart($this->createProduct());
        $this->submitCustomerStep();
        $this->submitDeliveryStep();

        $this
            ->asGuest()
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('checkout-steps__item--current', false)
            ->assertSee('checkout-steps__item--complete', false)
            ->assertSee('checkout-steps__item--pending', false)
            // The two answered steps lead back to their own form.
            ->assertSee('href="'.CheckoutStep::CUSTOMER->url().'"', false);
    }

    public function test_the_heading_and_the_flow_map_keep_the_normal_measure_and_only_the_layout_is_wide(): void
    {
        $this->addToCart($this->createProduct());

        $this
            ->asGuest()
            ->get(route('checkout.index'))
            ->assertOk()
            // The heading and the stepper stay on the page's own measure…
            ->assertSee('<header class="checkout__header page-header page-header--spaced wrapper">', false)
            ->assertSee('<ol class="checkout-steps wrapper"', false)
            // …while the step and its summary take the wide one.
            ->assertSee('<div class="checkout-layout wrapper wrapper--wide">', false);
    }

    public function test_the_review_links_back_to_each_step_it_shows(): void
    {
        $this->addToCart($this->createProduct());
        $this->walkToReview();

        $html = $this
            ->asGuest()
            ->get(CheckoutStep::REVIEW->url())
            ->assertOk()
            ->getContent();

        // Only what the step panel renders: the stepper above it is left out,
        // so these are the review's own edit links.
        $panel = Str::after($html, 'checkout-step__body');

        foreach (CheckoutStep::formSteps() as $step) {
            static::assertStringContainsString(
                'href="'.$step->url().'"',
                $panel,
                "The review does not link back to the [{$step->value}] step.",
            );
        }
    }

    public function test_every_review_block_is_headed_by_the_step_it_restates(): void
    {
        $this->addToCart($this->createProduct());
        $this->walkToReview();

        $panel = Str::after(
            $this
                ->asGuest()
                ->get(CheckoutStep::REVIEW->url())
                ->assertOk()
                ->getContent(),
            'checkout-step__body',
        );

        // Each answered step wears its own number and name, as the step itself
        // does — the items are the only block that is not a step.
        static::assertSame(
            count(CheckoutStep::formSteps()),
            substr_count($panel, 'checkout-step__number'),
        );

        foreach (CheckoutStep::formSteps() as $step) {
            static::assertStringContainsString(
                '<h3 class="checkout-step__title">'.$step->label().'</h3>',
                $panel,
                "The review does not restate the [{$step->value}] step.",
            );
        }
    }

    public function test_a_guest_placing_the_order_is_sent_to_sign_in_and_returned_to_the_review(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $this->addToCart($this->createProduct());
        $this->walkToReview();

        $this->assertGuest();

        $this
            ->asGuest()
            ->from(CheckoutStep::REVIEW->url())
            ->post(route('checkout.place'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', CheckoutStep::REVIEW->url());

        // Signing in from there returns the shopper to the review, with the
        // cart and the answers still in place.
        $this
            ->asGuest()
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(CheckoutStep::REVIEW->url());

        $this
            ->asGuest()
            ->get(CheckoutStep::REVIEW->url())
            ->assertOk()
            ->assertSee(__('checkout.place.action'));
    }

    public function test_the_review_offers_a_guest_sign_in_and_register(): void
    {
        $this->addToCart($this->createProduct());
        $this->walkToReview();

        $this
            ->asGuest()
            ->get(CheckoutStep::REVIEW->url())
            ->assertOk()
            ->assertSee(__('checkout.place.guest_notice'))
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('href="'.route('register').'"', false)
            // The customer's own details are still what they typed.
            ->assertSee('ada@example.com');
    }

    public function test_the_checkout_prefills_the_accounts_contact_details(): void
    {
        $account = $this->accountWithDetails();

        $this->actingAs($account['user']);
        $this->addToCart($this->createProduct());

        $this
            ->asGuest()
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('value="Layla Hassan"', false)
            ->assertSee('value="layla@example.com"', false)
            ->assertSee('value="39000000"', false);
    }

    public function test_the_checkout_prefills_the_address_on_the_account(): void
    {
        $account = $this->accountWithDetails();

        $this->actingAs($account['user']);
        $this->addToCart($this->createProduct());
        $this->submitCustomerStep([
            'name' => 'Layla Hassan',
            'email' => 'layla@example.com',
        ]);
        $this->submitDeliveryStep();

        $this
            ->asGuest()
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('value="12 Road 1234"', false)
            ->assertSee('value="Manama"', false)
            ->assertSee('value="1234"', false)
            // The country on file comes back chosen in the select.
            ->assertSee('selected', false);
    }

    public function test_the_checkout_fills_the_account_details_in_after_signing_in(): void
    {
        $user = User::factory()->create([
            'name' => 'Layla Hassan',
            'email' => 'layla@example.com',
            'password' => 'password',
        ]);
        $this->addToCart($this->createProduct());

        // A guest opens the checkout, so the contact step is blank...
        $this
            ->asGuest()
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertDontSee('value="Layla Hassan"', false);

        $this->asGuest()->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // ...and their details are waiting for them afterwards.
        $this
            ->asGuest()
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('value="Layla Hassan"', false)
            ->assertSee('value="layla@example.com"', false);
    }

    public function test_a_signed_in_shoppers_answers_are_kept_on_their_account(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->addToCart($this->createProduct());
        $this->openCheckout();

        $this->submitCustomerStep([
            'name' => 'Layla Hassan',
            'email' => 'layla@example.com',
        ]);

        $draft = CheckoutDraft::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        static::assertSame(1, $draft->state['progress']);
        static::assertSame(
            'Layla Hassan',
            $draft->state[CheckoutStep::CUSTOMER->value]['name'],
        );
    }

    public function test_a_signed_in_shopper_returns_to_the_draft_they_left_behind(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->addToCart($this->createProduct());

        // A draft left halfway through the flow...
        CheckoutDraft::factory()->forUser($user)->create();

        // ...and a session that has since expired.
        $this->flushSession();

        $this
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee(__('checkout.steps.delivery.title'));

        $this
            ->get(CheckoutStep::CUSTOMER->url())
            ->assertOk()
            ->assertSee('value="Ada Lovelace"', false);
    }

    public function test_a_guest_leaves_no_draft_on_any_account(): void
    {
        $this->addToCart($this->createProduct());
        $this->openCheckout();

        $this->submitCustomerStep();

        static::assertSame(0, CheckoutDraft::query()->count());
    }

    public function test_a_signed_in_shopper_can_walk_the_whole_flow(): void
    {
        $user = User::factory()->create([
            'name' => 'Layla Hassan',
            'email' => 'layla@example.com',
        ]);
        $this->actingAs($user);
        $this->addToCart($this->createProduct());

        $this
            ->asGuest()
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('value="Layla Hassan"', false)
            ->assertSee('value="layla@example.com"', false);

        $this->submitCustomerStep([
            'name' => 'Layla Hassan',
            'email' => 'layla@example.com',
        ]);
        $this->submitDeliveryStep();
        $this->submitShippingStep();
        $this->submitPaymentStep()->assertRedirect(CheckoutStep::REVIEW->url());

        $this
            ->asGuest()
            ->get(CheckoutStep::REVIEW->url())
            ->assertOk()
            ->assertSee('layla@example.com')
            ->assertDontSee(__('checkout.place.guest_notice'));

        // Placing the order creates it, empties the cart and lands on the
        // confirmation that carries the order's access token.
        $this
            ->asGuest()
            ->post(route('checkout.place'), [
                'checkout_token' => session('checkout.token'),
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('cart_items', 0);
        static::assertNull(session('checkout'));
    }

    public function test_the_checkout_answers_and_the_cart_survive_signing_in(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $product = $this->createProduct();
        $this->addToCart($product, quantity: 2);
        $this->walkToReview();

        $this
            ->asGuest()
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('home'));

        static::assertSame(
            'ada@example.com',
            session('checkout.'.CheckoutStep::CUSTOMER->value.'.email'),
        );
        static::assertSame(4, session('checkout.progress'));

        // The guest cart was folded onto the account, and the review is still
        // reachable because every step is still answered.
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this
            ->get(CheckoutStep::REVIEW->url())
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_the_checkout_renders_in_arabic_and_mirrors_the_layout(): void
    {
        $this->addToCart($this->createProduct());

        // Switch the shopper to Arabic the way the header's language control
        // does, so the page is rendered through the locale middleware.
        $this->asGuest()->post(route('lang'), ['language' => 'ar']);

        $this
            ->asGuest()
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee(__('checkout.title', locale: 'ar'))
            ->assertSee(__('checkout.steps.customer.title', locale: 'ar'))
            ->assertSee(__('checkout.actions.continue', locale: 'ar'))
            ->assertSee(__('carts.summary.total', locale: 'ar'));
    }

    public function test_the_shipping_step_only_offers_to_save_the_address_to_a_signed_in_shopper(): void
    {
        $this->addToCart($this->createProduct());
        $this->submitCustomerStep();
        $this->submitDeliveryStep();

        $this
            ->asGuest()
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertDontSee('name="save_to_account"', false);
    }

    public function test_the_shipping_step_offers_to_save_the_address_to_a_signed_in_shopper(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->addToCart($this->createProduct());
        $this->submitCustomerStep();
        $this->submitDeliveryStep();

        $this
            ->asGuest()
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('name="save_to_account"', false);
    }

    /**
     * An account that already holds the name, email, phone and address the
     * checkout should fill in from.
     *
     * @return array{user: User, country: Country, address: Address}
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

        $address = Address::factory()
            ->user($user)
            ->addressType(AddressType::SHIPPING)
            ->country($country)
            ->create([
                'street_address' => '12 Road 1234',
                'city' => 'Manama',
                'state' => 'Capital',
                'postal_code' => '1234',
                'defaulted_at' => now(),
            ]);

        return ['user' => $user, 'country' => $country, 'address' => $address];
    }

    private function asGuest(): static
    {
        return $this->withCookie(config('session.cookie'), self::SESSION_ID);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createProduct(array $attributes = []): Product
    {
        return Product::factory()
            ->active()
            ->create(
                array_merge(
                    [
                        'stock' => 10,
                        'reserved_stock' => 0,
                        'low_stock_threshold' => 0,
                    ],
                    $attributes,
                ),
            );
    }

    private function addToCart(
        Product $product,
        int $quantity = 1,
    ): TestResponse {
        return $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }

    /**
     * Open the checkout page, which is what seeds the flow's state.
     */
    private function openCheckout(): void
    {
        $this->asGuest()->get(route('checkout.index'))->assertOk();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function address(array $overrides = []): array
    {
        return array_merge(
            [
                'street_address' => '12 Road 1234',
                'city' => 'Manama',
                'state' => 'Capital',
                'country_id' => Country::factory()->bahrain()->create()->id,
                'postal_code' => '1234',
            ],
            $overrides,
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function submitCustomerStep(array $overrides = []): TestResponse
    {
        return $this->asGuest()->post(
            route('checkout.customer.store'),
            array_merge(
                [
                    'name' => 'Ada Lovelace',
                    'email' => 'ada@example.com',
                    'country_id' => $this->bahrain()->id,
                    'phone_number' => '39000000',
                ],
                $overrides,
            ),
        );
    }

    /**
     * The country the flow's numbers are given for — made once, so the state a
     * step stores and the state a test asserts name the same one.
     */
    private function bahrain(): Country
    {
        return $this->bahrain ??= Country::factory()->bahrain()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function submitShippingStep(array $overrides = []): TestResponse
    {
        return $this->asGuest()->post(
            route('checkout.shipping.store'),
            $this->address($overrides),
        );
    }

    private function submitDeliveryStep(array $overrides = []): TestResponse
    {
        return $this->asGuest()->post(
            route('checkout.delivery.store'),
            array_merge(
                ['delivery_method' => DeliveryMethod::STANDARD->value],
                $overrides,
            ),
        );
    }

    private function submitPaymentStep(): TestResponse
    {
        return $this->asGuest()->post(route('checkout.payment.store'), [
            'payment_method' => PaymentMethod::COD->value,
        ]);
    }

    /**
     * Answer every step up to payment, so the shopper stands on the payment step.
     */
    private function walkToPayment(): void
    {
        $this->submitCustomerStep();
        $this->submitDeliveryStep();
        $this->submitShippingStep();
    }

    /**
     * Answer every step up to and including payment, which lands the shopper on
     * the review.
     */
    private function walkToReview(): void
    {
        $this->walkToPayment();
        $this->submitPaymentStep();
    }
}
