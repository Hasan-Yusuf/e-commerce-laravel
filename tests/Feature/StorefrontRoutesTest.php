<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CheckoutStep;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Country;
use App\Models\Order;
use App\Models\Product;
use App\Models\Type;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * A smoke test over the storefront's pages: every GET route is visited in the
 * state a shopper reaches it in, and must answer 200. It catches a page that
 * has stopped rendering — a broken route binding, an exception behind a filter,
 * a view that lost the data it reads — for the routes no other feature test
 * happens to walk.
 *
 * Only the GET routes are visited: the POST/PATCH/DELETE routes are actions
 * that answer with a redirect by design, not pages that carry an OK status.
 */
class StorefrontRoutesTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * A fixed session id (40 alphanumeric chars) so the guest's cart and
     * checkout share one session: the test client does not replay the session
     * cookie on its own, so it is sent with every request instead.
     */
    private const string SESSION_ID = 'StorefrontRoutesTest01234567890123456789';

    private ?Category $category = null;

    private ?Type $type = null;

    private ?Product $product = null;

    private ?User $user = null;

    private ?Order $order = null;

    public function test_the_home_page_responds_with_ok(): void
    {
        $this->asGuest();

        $this->assertPageOk('home', route('home'));
    }

    public function test_the_products_pages_respond_with_ok(): void
    {
        $this->asGuest();

        $this->assertPageOk('products.index', route('products.index'));
        $this->assertPageOk(
            'products.show',
            route('products.show', $this->product()->slug),
        );
    }

    public function test_the_category_pages_respond_with_ok(): void
    {
        $this->asGuest();

        $this->assertPageOk('category.index', route('category.index'));
        $this->assertPageOk(
            'category.show',
            route('category.show', $this->category()->slug),
        );
    }

    public function test_the_cart_page_responds_with_ok(): void
    {
        $this->asGuest();

        $this->assertPageOk('cart.index', route('cart.index'));
    }

    public function test_the_contact_page_responds_with_ok(): void
    {
        $this->asGuest();

        $this->assertPageOk('contact.index', route('contact.index'));
    }

    public function test_the_sign_in_page_responds_with_ok(): void
    {
        $this->asGuest();

        $this->assertPageOk('login', route('login'));
    }

    public function test_the_registration_page_responds_with_ok(): void
    {
        $this->asGuest();

        $this->assertPageOk('register', route('register'));
    }

    public function test_the_forgot_password_page_responds_with_ok(): void
    {
        $this->asGuest();

        $this->assertPageOk('password.request', route('password.request'));
    }

    public function test_the_checkout_pages_respond_with_ok(): void
    {
        $this->asGuest();

        Country::factory()->bahrain()->create();

        // The checkout opens only once the cart holds a line.
        $this->post(route('cart.add'), [
            'product_id' => $this->product()->id,
            'quantity' => 1,
        ]);

        $this->assertPageOk('checkout.index', route('checkout.index'));
        $this->assertPageOk(
            'checkout.step',
            CheckoutStep::firstStep()->url(),
        );
    }

    public function test_the_order_confirmation_page_responds_with_ok(): void
    {
        $this->asGuest();

        $order = $this->order();

        $this->assertPageOk(
            'checkout.confirmation',
            route('checkout.confirmation', [
                'order' => $order->number,
                'token' => $order->access_token,
            ]),
        );
    }

    public function test_the_account_pages_respond_with_ok(): void
    {
        $this->actingAs($this->user());

        $this->assertPageOk('account.index', route('account.index'));
        $this->assertPageOk('account.edit', route('account.edit'));
    }

    public function test_the_orders_pages_respond_with_ok(): void
    {
        $this->actingAs($this->user());

        $this->assertPageOk('orders.index', route('orders.index'));
        $this->assertPageOk(
            'orders.show',
            route('orders.show', $this->order()->number),
        );
    }

    /**
     * Visit a page and fail with the route it belongs to, so a broken route is
     * named rather than just counted.
     */
    private function assertPageOk(string $route, string $url): void
    {
        $response = $this->get($url);

        static::assertTrue(
            $response->isOk(),
            "The \"{$route}\" page ({$url}) responded with HTTP {$response->status()} instead of 200.",
        );
    }

    private function asGuest(): static
    {
        return $this->withCookie(config('session.cookie'), self::SESSION_ID);
    }

    private function category(): Category
    {
        return $this->category ??= Category::factory()
            ->active()
            ->create(['parent_id' => null]);
    }

    private function type(): Type
    {
        return $this->type ??= Type::factory()->create([
            'category_id' => $this->category()->id,
        ]);
    }

    private function product(): Product
    {
        return $this->product ??= Product::factory()
            ->active()
            ->create([
                'type_id' => $this->type()->id,
                'stock' => 5,
                'reserved_stock' => 0,
            ]);
    }

    private function user(): User
    {
        return $this->user ??= User::factory()->create([
            'role' => UserRole::CUSTOMER,
        ]);
    }

    private function order(): Order
    {
        return $this->order ??= Order::factory()->create([
            'user_id' => $this->user()->id,
        ]);
    }
}
