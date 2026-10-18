<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderHistoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_history_lists_the_accounts_orders(): void
    {
        $user = User::factory()->create();
        $order = $this->placedOrderFor($user);

        $this
            ->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee($order->number)
            ->assertSee(route('orders.show', $order), false)
            ->assertSee(__('orders.index.view'));
    }

    public function test_the_history_is_empty_until_an_order_is_placed(): void
    {
        $this
            ->actingAs(User::factory()->create())
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee(__('orders.empty.title'))
            ->assertSee(__('orders.empty.message'));
    }

    public function test_the_history_only_ever_lists_the_accounts_own_orders(): void
    {
        $user = User::factory()->create();
        $someoneElses = $this->placedOrderFor(User::factory()->create());

        $this
            ->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertDontSee($someoneElses->number);
    }

    public function test_the_history_shows_as_many_orders_as_the_shopper_asks_for(): void
    {
        $user = User::factory()->create();

        $orders = collect(range(1, 6))->map(
            fn (): Order => $this->placedOrderFor($user),
        );

        $oldest = $orders->first();
        $newest = $orders->last();

        // Ten orders to a page by default, so all six of them are here...
        $this
            ->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee($oldest?->number)
            ->assertSee($newest?->number);

        // ...five at a time shows the five newest, and the choice is offered on
        // the page and carried into the links to the rest of the history.
        $this
            ->get(route('orders.index', ['per_page' => 5]))
            ->assertOk()
            ->assertSee($newest?->number)
            ->assertDontSee($oldest?->number)
            ->assertSee('name="per_page"', false)
            ->assertSee('per_page=5', false)
            ->assertSee(__('orders.index.per_page'));

        // A count the page does not offer is the default, not an error.
        $this
            ->get(route('orders.index', ['per_page' => 'nonsense']))
            ->assertOk()
            ->assertSee($oldest?->number);
    }

    public function test_an_order_shows_the_lines_address_payment_and_totals(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()
            ->active()
            ->create([
                'price_in_fils' => 12_500,
            ]);

        $order = $this->placedOrderFor($user, $product, quantity: 2);

        $this
            ->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee($order->number)
            ->assertSee($product->name)
            ->assertSee(__('orders.show.address'))
            ->assertSee(__('orders.summary.subtotal'))
            ->assertSee(__('orders.summary.total'))
            ->assertSee('25 BHD')
            ->assertSee(__('orders.show.breadcrumb'))
            ->assertSeeInOrder([
                __('orders.show.title', ['number' => $order->number]),
                __('orders.summary.total'),
                $order->shippingAddress->street_address,
                __('orders.show.payment'),
            ]);
    }

    public function test_a_shopper_cannot_open_someone_elses_order(): void
    {
        $someoneElses = $this->placedOrderFor(User::factory()->create());

        $this
            ->actingAs(User::factory()->create())
            ->get(route('orders.show', $someoneElses))
            ->assertNotFound();
    }

    public function test_an_order_is_addressed_by_its_number_rather_than_its_id(): void
    {
        $user = User::factory()->create();
        $order = $this->placedOrderFor($user);

        $this->actingAs($user);

        static::assertSame(
            route('orders.show', $order->number),
            route('orders.show', $order),
        );

        $this->get(route('orders.show', $order))->assertOk();

        $this->get('/orders/'.$order->id)->assertNotFound();
        $this->get('/checkout/confirmation/'.$order->id)->assertNotFound();
    }

    public function test_the_confirmation_opens_with_the_orders_access_token(): void
    {
        $order = $this->placedOrderFor(User::factory()->create());

        $this
            ->get($this->confirmationUrl($order))
            ->assertOk()
            ->assertSee($order->number)
            ->assertSee(__('orders.confirmation.title'))
            ->assertSee(__('orders.confirmation.estimated_delivery'))
            // A guest has no order to open in the account, so no link to one.
            ->assertDontSee(route('orders.show', $order), false);
    }

    public function test_the_confirmation_is_refused_without_a_valid_token(): void
    {
        $order = $this->placedOrderFor(User::factory()->create());

        $confirmation = route('checkout.confirmation', [
            'order' => $order->number,
        ]);

        $this->get($confirmation)->assertNotFound();

        $this->get(
            $confirmation.'?token='.(string) Str::uuid(),
        )->assertNotFound();
    }

    public function test_the_confirmation_is_offered_to_the_orders_own_account(): void
    {
        $user = User::factory()->create();
        $order = $this->placedOrderFor($user);

        $this
            ->actingAs($user)
            ->get(route('checkout.confirmation', ['order' => $order->number]))
            ->assertOk()
            ->assertSee($order->number)
            ->assertSee(route('orders.show', $order), false);
    }

    public function test_the_confirmation_held_by_token_offers_a_stranger_no_order(): void
    {
        $order = $this->placedOrderFor(User::factory()->create());

        // The token opens the confirmation it was handed out with — that is all
        // a guest has — but the order itself stays the account's.
        $this
            ->actingAs(User::factory()->create())
            ->get($this->confirmationUrl($order))
            ->assertOk()
            ->assertSee($order->number)
            ->assertDontSee(route('orders.show', $order), false);
    }

    public function test_the_history_needs_an_account(): void
    {
        $order = $this->placedOrderFor(User::factory()->create());

        $this->get(route('orders.index'))->assertRedirect(route('login'));
        $this->get(route('orders.show', $order))->assertRedirect(
            route('login'),
        );
    }

    public function test_an_unknown_order_number_is_not_found(): void
    {
        $this->get('/checkout/confirmation/ORD-00000000')->assertNotFound();
    }

    public function test_the_orders_pages_render_in_arabic_and_mirror_the_layout(): void
    {
        $user = User::factory()->create();
        $this->placedOrderFor($user);

        $this->actingAs($user);

        // Switch the shopper to Arabic the way the header's control does, so the
        // page is rendered through the locale middleware.
        $this->post(route('lang'), ['language' => 'ar']);

        $this
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee(__('orders.title', locale: 'ar'))
            ->assertSee(__('orders.index.number', locale: 'ar'));
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

    public function test_the_order_details_read_the_note_the_shopper_left_back(): void
    {
        $user = User::factory()->create();
        $order = $this->placedOrderFor($user);
        $order->update(['notes' => 'Please call before delivery.']);

        $this
            ->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee(__('orders.show.note'))
            ->assertSee('Please call before delivery.');
    }

    /**
     * An order as placing one writes it: its line, its address, its payment.
     */
    private function placedOrderFor(
        User $user,
        ?Product $product = null,
        int $quantity = 1,
    ): Order {
        $product ??= Product::factory()->active()->create();

        // The money is what the order was placed for: the line, with no tax,
        // delivery or discount on top of it — the same shape placing one
        // writes (the model totals the columns as they are set).
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'subtotal_in_fils' => $product->price_in_fils->toFils() * $quantity,
            'tax_amount_in_fils' => 0,
            'shipping_amount_in_fils' => 0,
            'discount_amount_in_fils' => 0,
        ]);

        OrderItem::factory()
            ->withOrder($order)
            ->withProduct($product)
            ->create([
                'product_name' => $product->name,
                'sku' => $product->sku,
                'quantity' => $quantity,
                'unit_price_in_fils' => $product->price_in_fils->toFils(),
                'total_price_in_fils' => $product->price_in_fils->toFils() * $quantity,
            ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'amount_in_fils' => $order->total_amount_in_fils,
        ]);

        return $order->refresh();
    }
}
