<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CheckoutStep;
use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\CartItem;
use App\Models\Country;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class OrderPlacementTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * A fixed session id, so the requests within a test share one cart and one
     * set of checkout answers (the test client does not replay the cookie).
     */
    private const string SESSION_ID = 'OrderPlacementSession01234567890123456789';

    public function test_placing_an_order_writes_the_order_its_lines_and_its_payment(): void
    {
        $user = $this->signedInUser();
        $product = $this->createProduct(['price_in_fils' => 12_500]);

        $this->addToCart($product, quantity: 2);
        $this->walkToReview();

        $this->place()->assertRedirect();

        $order = Order::query()->firstOrFail();

        static::assertSame($user->id, $order->user_id);
        static::assertSame(OrderStatus::PENDING, $order->status);
        static::assertMatchesRegularExpression('/^ORD-\d{8}$/', $order->number);

        // The money is the cart's, summed in fils: 2 × 12.500 BHD.
        static::assertSame(25_000, $order->subtotal_in_fils->toFils());
        static::assertSame(0, $order->tax_amount_in_fils->toFils());
        static::assertSame(0, $order->shipping_amount_in_fils->toFils());
        static::assertSame(0, $order->discount_amount_in_fils->toFils());
        static::assertSame(25_000, $order->total_amount_in_fils->toFils());

        // Nothing was picked to collect it, so it is delivered to the address.
        static::assertSame(DeliveryMethod::STANDARD, $order->delivery_method);

        // The line is a snapshot: what was bought, at the price it was bought
        // for, kept on the order rather than read back off the product.
        $item = $order->orderItems()->firstOrFail();

        static::assertSame($product->id, $item->product_id);
        static::assertSame($product->name, $item->product_name);
        static::assertSame($product->sku, $item->sku);
        static::assertSame(2, (int) $item->quantity);
        static::assertSame(12_500, $item->unit_price_in_fils->toFils());
        static::assertSame(25_000, $item->total_price_in_fils->toFils());

        // Cash on delivery is recorded as a payment that is still pending.
        $payment = $order->payments()->firstOrFail();

        static::assertSame(PaymentMethod::COD, $payment->method);
        static::assertSame(PaymentStatus::PENDING, $payment->status);
        static::assertSame(25_000, $payment->amount_in_fils->toFils());
        static::assertNull($payment->paid_at);
    }

    public function test_placing_an_order_holds_the_stock_it_takes(): void
    {
        $this->signedInUser();
        $product = $this->createProduct(['stock' => 10]);

        $this->addToCart($product, quantity: 3);
        $this->walkToReview();

        $this->place();

        static::assertSame(3, (int) $product->refresh()->reserved_stock);
        static::assertSame(7, $product->getAvailableStock());
    }

    public function test_placing_an_order_empties_the_cart_and_the_checkout(): void
    {
        $user = $this->signedInUser();
        $product = $this->createProduct();

        $this->addToCart($product);
        $this->walkToReview();

        $this->place();

        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseMissing('checkout_drafts', [
            'user_id' => $user->id,
        ]);
        static::assertNull(session('checkout'));
    }

    public function test_placing_an_order_keeps_the_address_it_was_shipped_to(): void
    {
        $this->signedInUser();
        $this->addToCart($this->createProduct());
        $this->walkToReview();

        $this->place();

        $order = Order::query()->firstOrFail();

        static::assertNotNull($order->shipping_address_id);
        static::assertTrue($order->isShippingBillingSame());
        static::assertSame(
            '12 Road 1234',
            $order->shippingAddress->street_address,
        );
    }

    public function test_placing_an_order_writes_the_note_it_was_placed_with(): void
    {
        $this->signedInUser();
        $this->addToCart($this->createProduct());
        $this->walkToReview();

        $this->place(['notes' => 'Please call before delivery.']);

        static::assertSame(
            'Please call before delivery.',
            Order::query()->firstOrFail()->notes,
        );
    }

    public function test_an_order_placed_without_a_note_keeps_none(): void
    {
        $this->signedInUser();
        $this->addToCart($this->createProduct());
        $this->walkToReview();

        // The field is the shopper's to leave empty, and whitespace is not a
        // note: nothing is kept for either.
        $this->place(['notes' => "   \n  "]);

        static::assertNull(Order::query()->firstOrFail()->notes);
    }

    public function test_a_note_longer_than_the_field_allows_is_refused(): void
    {
        $this->signedInUser();
        $this->addToCart($this->createProduct());
        $this->walkToReview();

        $this->place(['notes' => str_repeat('a', 1001)])
            ->assertSessionHasErrors('notes');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_the_note_a_refused_placement_carried_comes_back_to_the_review(): void
    {
        $this->signedInUser();
        $product = $this->createProduct(['stock' => 10]);

        $this->addToCart($product, quantity: 4);
        $this->walkToReview();

        // Most of it is sold while the shopper sits on the review.
        $product->update(['stock' => 2]);

        $this->place(['notes' => 'Leave it with the building reception.'])
            ->assertSessionHas('stock_changed');

        // The order is placed again from where it was refused, so the note is
        // still in the field rather than left to be written out a second time.
        $this
            ->get(CheckoutStep::REVIEW->url())
            ->assertOk()
            ->assertSee(__('checkout.place.note'))
            ->assertSee('Leave it with the building reception.');
    }

    public function test_placing_an_order_lands_on_its_confirmation(): void
    {
        $this->signedInUser();
        $this->addToCart($this->createProduct());
        $this->walkToReview();

        $this->place()->assertRedirect(
            $this->confirmationUrl(Order::query()->firstOrFail()),
        );
    }

    public function test_a_resubmitted_checkout_places_one_order_only(): void
    {
        $this->signedInUser();
        $this->addToCart($this->createProduct());

        $this->walkToReview();

        $token = (string) session('checkout.token');

        $this->place(['checkout_token' => $token])->assertRedirect();

        $order = Order::query()->firstOrFail();

        // The same submission again — a refresh, the back button, a double
        // click — is answered with the order it already placed, and places
        // nothing: the cart it would have used is empty by now.
        $this->place(['checkout_token' => $token])->assertRedirect(
            $this->confirmationUrl($order),
        );

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_stock_that_changed_mid_checkout_blocks_the_order_and_updates_the_cart(): void
    {
        $this->signedInUser();
        $product = $this->createProduct(['stock' => 10]);

        $this->addToCart($product, quantity: 4);
        $this->walkToReview();

        // Most of it is sold while the shopper sits on the review.
        $product->update(['stock' => 2]);

        $this
            ->place()
            ->assertRedirect(CheckoutStep::REVIEW->url())
            ->assertSessionHas('stock_changed');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('payments', 0);

        // The cart is what changed: it now holds what can actually be ordered,
        // and nothing was reserved.
        static::assertSame(2, (int) CartItem::query()->firstOrFail()->quantity);
        static::assertSame(0, (int) $product->refresh()->reserved_stock);

        $this
            ->get(CheckoutStep::REVIEW->url())
            ->assertOk()
            ->assertSee(__('checkout.place.stock_changed_title'))
            ->assertSee(__('checkout.place.stock_changed_message'));
    }

    public function test_a_line_whose_product_left_the_catalogue_blocks_the_order(): void
    {
        $this->signedInUser();
        $product = $this->createProduct();

        $this->addToCart($product);
        $this->walkToReview();

        $product->update(['activated_at' => null]);

        $this->place()->assertSessionHas('stock_changed');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_checkout_that_has_not_been_answered_cannot_be_placed(): void
    {
        $this->signedInUser();
        $this->addToCart($this->createProduct());

        // Answering nothing but the contact step, the flow stands on the
        // delivery step — and that is where a placement from there is sent.
        $this->asGuest()->get(route('checkout.index'))->assertOk();
        $this->submitCustomerStep();

        $this->place()->assertRedirect(CheckoutStep::DELIVERY->url());

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_placing_with_an_empty_cart_returns_to_the_cart(): void
    {
        $this->signedInUser();

        $this->place()->assertRedirect(route('cart.index'));

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_placing_an_order_records_the_collection_it_was_placed_with(): void
    {
        $this->signedInUser();
        $this->addToCart($this->createProduct());
        $this->walkToReview(deliveryMethod: DeliveryMethod::PICKUP);

        $this->place();

        $order = Order::query()->firstOrFail();

        // The shop knows how the order is to be fulfilled...
        static::assertSame(DeliveryMethod::PICKUP, $order->delivery_method);

        // ...and the confirmation tells the shopper to come for it rather than
        // waiting on a delivery.
        $this
            ->get($this->confirmationUrl($order))
            ->assertOk()
            ->assertSee(__('orders.confirmation.ready_for_pickup'))
            ->assertSee(DeliveryMethod::PICKUP->getLabel())
            ->assertDontSee(__('orders.confirmation.estimated_delivery'));
    }

    /**
     * A shopper with the answers of a checkout that is ready to place.
     */
    private function walkToReview(
        DeliveryMethod $deliveryMethod = DeliveryMethod::STANDARD,
    ): void {
        $this->submitCustomerStep();
        $this->asGuest()->post(route('checkout.delivery.store'), [
            'delivery_method' => $deliveryMethod->value,
        ]);

        // Collecting the order from the shop has no address to answer.
        if (! $deliveryMethod->is(DeliveryMethod::PICKUP)) {
            $this->submitShippingStep();
        }

        $this->asGuest()->post(route('checkout.payment.store'), [
            'payment_method' => PaymentMethod::COD->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function place(array $overrides = []): TestResponse
    {
        return $this->asGuest()->post(
            route('checkout.place'),
            array_merge(
                ['checkout_token' => session('checkout.token')],
                $overrides,
            ),
        );
    }

    private function submitCustomerStep(): TestResponse
    {
        return $this->asGuest()->post(route('checkout.customer.store'), [
            'name' => 'Layla Hassan',
            'email' => 'layla@example.com',
            // The number is given for a country, whose numbering plan says how
            // long it is (Bahrain: eight digits).
            'country_id' => Country::factory()->bahrain()->create()->id,
            'phone_number' => '39000000',
        ]);
    }

    private function submitShippingStep(): TestResponse
    {
        return $this->asGuest()->post(route('checkout.shipping.store'), [
            'street_address' => '12 Road 1234',
            'city' => 'Manama',
            'state' => 'Capital',
            'country_id' => Country::factory()->bahrain()->create()->id,
            'postal_code' => '1234',
        ]);
    }

    /**
     * The confirmation URL the storefront hands back: the order's number and the
     * access token that opens the page.
     */
    private function confirmationUrl(Order $order): string
    {
        return route('checkout.confirmation', [
            'order' => $order->number,
            'token' => $order->access_token,
        ]);
    }

    private function signedInUser(): User
    {
        $user = User::factory()->create([
            'name' => 'Layla Hassan',
            'email' => 'layla@example.com',
        ]);

        $this->actingAs($user);

        return $user;
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
}
