<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_overview_shows_the_account_and_its_recent_orders(): void
    {
        $user = User::factory()->create();
        $order = $this->placedOrderFor($user);

        $this
            ->actingAs($user)
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email)
            ->assertSee(__('account.details.member_since'))
            ->assertSee($user->created_at->translatedFormat('F Y'))
            // The recent orders, and the way to the whole history...
            ->assertSee($order->number)
            ->assertSee(route('orders.show', $order), false)
            ->assertSee(route('orders.index'), false)
            ->assertSee(__('account.orders.view_all'))
            // The wishlist is not a tab here: it stays under the cart, which the
            // header's cart button reaches.
            ->assertDontSee(route('cart.index').'#wishlist', false);
    }

    public function test_the_overview_lists_only_the_accounts_own_orders(): void
    {
        $user = User::factory()->create();
        $someoneElses = $this->placedOrderFor(User::factory()->create());

        $this
            ->actingAs($user)
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee(__('account.orders.empty.title'))
            ->assertDontSee($someoneElses->number)
            ->assertDontSee(route('orders.show', $someoneElses), false);
    }

    public function test_the_overview_shows_the_newest_orders_and_no_more(): void
    {
        $user = User::factory()->create();

        $orders = collect(range(1, OrderService::RECENT_LIMIT + 1))->map(fn (): Order => $this->placedOrderFor($user));

        $this
            ->actingAs($user)
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee($orders->last()?->number)
            ->assertDontSee($orders->first()?->number);
    }

    public function test_the_overview_invites_a_shopper_who_has_not_ordered_yet(): void
    {
        $this
            ->actingAs(User::factory()->create())
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee(__('account.orders.empty.title'))
            ->assertSee(__('account.orders.empty.action'))
            // Nothing to hand the shopper over to, so no link to a history that
            // is not there.
            ->assertDontSee(__('account.orders.view_all'));
    }

    public function test_the_account_area_needs_an_account(): void
    {
        $this
            ->get(route('account.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', route('account.index'));

        // Changing the account needs one too: every write is behind the same
        // gate as the page that carries it.
        $this->get(route('account.edit'))->assertRedirect(route('login'));
        $this->patch(route('account.update'), [])->assertRedirect(
            route('login'),
        );
        $this->patch(route('account.address.update'), [])->assertRedirect(
            route('login'),
        );
        $this->put(route('account.password.update'), [])->assertRedirect(
            route('login'),
        );
    }

    public function test_the_navigation_marks_the_section_the_shopper_is_in(): void
    {
        $user = User::factory()->create();
        $order = $this->placedOrderFor($user);

        $this->actingAs($user);

        // On the overview the overview is the current section — and the only
        // one.
        $account = $this->get(route('account.index'))->assertOk()->getContent();

        static::assertMatchesRegularExpression(
            '/account-nav__link--active"\s+href="[^"]*account"/',
            $account,
        );
        static::assertSame(
            1,
            substr_count($account, 'account-nav__link--active'),
        );

        // The history, one order out of it, and the profile are each their own
        // section, and each marks itself while it is open.
        $sections = [
            route('orders.index') => 'orders',
            route('orders.show', $order) => 'orders',
            route('account.edit') => 'edit',
        ];

        foreach ($sections as $url => $section) {
            $page = $this->get($url)->assertOk()->getContent();

            static::assertMatchesRegularExpression(
                '/account-nav__link--active"\s+href="[^"]*'.$section.'"/',
                $page,
            );
            static::assertSame(
                1,
                substr_count($page, 'account-nav__link--active'),
            );
        }
    }

    public function test_the_overview_renders_in_arabic_and_mirrors_the_layout(): void
    {
        $this->actingAs(User::factory()->create());

        // Switch the shopper to Arabic the way the header's control does, so the
        // page is rendered through the locale middleware.
        $this->post(route('lang'), ['language' => 'ar']);

        $this
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee(__('account.title', locale: 'ar'))
            ->assertSee(__('account.nav.orders', locale: 'ar'));
    }

    /**
     * An order as placing one writes it: the money the shopper was quoted and
     * the payment its method records.
     */
    private function placedOrderFor(User $user, int $totalInFils = 12_500): Order
    {
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'subtotal_in_fils' => $totalInFils,
            'tax_amount_in_fils' => 0,
            'shipping_amount_in_fils' => 0,
            'discount_amount_in_fils' => 0,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'amount_in_fils' => $order->total_amount_in_fils,
        ]);

        return $order->refresh();
    }
}
