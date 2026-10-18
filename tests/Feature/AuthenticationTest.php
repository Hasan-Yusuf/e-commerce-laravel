<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\FlashMessage;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * A fixed, valid session id (40 alphanumeric chars) so guest requests within
     * a test share one session. The test client does not replay the session
     * cookie automatically, so it is sent with every request instead.
     */
    private const string SESSION_ID = 'AuthenticationTestSession123456789012345';

    public function test_guests_see_the_login_page(): void
    {
        $this
            ->get(route('login'))
            ->assertOk()
            ->assertSee('action="'.route('login.store').'"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="remember"', false)
            ->assertSee('('.__('components.form.required').')')
            ->assertSee(__('auth.login.subtitle'))
            ->assertSee(__('auth.login.alt'))
            ->assertSee(route('register'))
            ->assertSee(route('password.request'))
            // The card is centred in the middle of the page, not under the nav.
            ->assertSee('class="auth-page wrapper grid-center"', false)
            // The page's own stylesheet (loaded on top of the global ones).
            ->assertSee(Vite::asset('resources/css/features/auth.css'), false);
    }

    public function test_signed_in_shoppers_are_sent_home_from_the_login_page(): void
    {
        $this
            ->actingAs(User::factory()->create())
            ->get(route('login'))
            ->assertRedirect(route('home'));
    }

    public function test_the_login_page_renders_in_arabic_and_mirrors_the_layout(): void
    {
        $this
            ->withSession(['locale' => 'ar'])
            ->get(route('login'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee(__('auth.login.title', locale: 'ar'), false)
            ->assertSee(__('components.auth.email', locale: 'ar'), false)
            ->assertSee(__('components.auth.password', locale: 'ar'), false);
    }

    public function test_the_login_form_guards_against_a_double_post(): void
    {
        $this
            ->get(route('login'))
            ->assertOk()
            ->assertSee('data-submit-once', false);

        static::assertStringContainsString(
            'data-submit-once',
            file_get_contents(resource_path('js/components/form-submit.js')),
        );
    }

    public function test_a_failed_login_from_the_dialog_reopens_it_on_other_pages(): void
    {
        $this
            ->asGuest()
            ->from(route('home'))
            ->post(route('login.store'), [
                'email' => 'nobody@example.com',
                'password' => 'secret',
            ])
            ->assertRedirect(route('home'));

        $this
            ->asGuest()
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-open="true"', false);
    }

    public function test_the_login_page_shows_its_errors_without_pushing_the_dialog_over_them(): void
    {
        $this
            ->asGuest()
            ->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'nobody@example.com',
                'password' => 'secret',
            ])
            ->assertRedirect(route('login'));

        $response = $this
            ->asGuest()
            ->get(route('login'))
            ->assertOk()
            ->assertSee(__('auth.failed'))
            ->assertSee('id="login-email-error"', false)
            ->assertSee('data-open="false"', false);

        // The page's own field plus the dialog's login panel (hidden) carry the
        // message; the dialog's register panel, which shares the `email` name,
        // renders nothing because it reads the register bag.
        static::assertSame(
            2,
            substr_count($response->getContent(), 'class="form-error"'),
        );
    }

    public function test_a_shopper_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHas(
                FlashMessage::TITLE,
                __('auth.login.status', ['name' => $user->name]),
            );

        $this->assertAuthenticatedAs($user);
    }

    public function test_remember_me_persists_the_login(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ])->assertRedirect(route('home'));

        $cookie = $response->getCookie($this->recallerCookieName(), false);

        static::assertNotNull($cookie);

        // A session cookie would go with the browser; "remember me" outlives it.
        static::assertGreaterThan(time(), $cookie->getExpiresTime());
    }

    public function test_logging_in_without_remember_me_leaves_no_recaller_cookie(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('home'))
            ->assertCookieMissing($this->recallerCookieName());
    }

    public function test_wrong_credentials_show_one_generic_error(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        // A wrong password for a real account...
        $this
            ->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrorsIn('login', ['email' => __('auth.failed')]);

        // ...and an address with no account produce the very same message, so
        // the form never reveals whether an account exists.
        $this
            ->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'nobody@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrorsIn('login', ['email' => __('auth.failed')]);

        $this->assertGuest();
    }

    public function test_login_validates_the_shape_of_the_credentials(): void
    {
        $this
            ->from(route('login'))
            ->post(route('login.store'), [])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrorsIn('login', ['email', 'password']);
    }

    public function test_the_login_form_renders_its_errors_accessibly(): void
    {
        $this
            ->asGuest()
            ->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'not-an-email',
                'password' => 'secret',
            ])
            ->assertRedirect(route('login'));

        $this
            ->asGuest()
            ->get(route('login'))
            ->assertOk()
            ->assertSee('class="form-error"', false)
            ->assertSee('id="login-email-error"', false)
            ->assertSee('aria-describedby="login-email-error"', false)
            ->assertSee('aria-invalid="true"', false);
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_login_carries_the_guest_cart_and_wishlist_onto_the_account(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $product = $this->createProduct();

        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $product->id,
        ]);

        $this
            ->asGuest()
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'session_id' => null,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('wishlist_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'session_id' => null,
        ]);
        $this->assertDatabaseMissing('carts', [
            'session_id' => self::SESSION_ID,
        ]);
    }

    public function test_login_returns_the_shopper_to_the_page_they_wanted(): void
    {
        Route::middleware('auth')->get('/protected-page', static fn (): string => 'ok');

        $user = User::factory()->create(['password' => 'password']);

        $this
            ->asGuest()
            ->get('/protected-page')
            ->assertRedirect(route('login'));

        $this
            ->asGuest()
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect('/protected-page');
    }

    public function test_a_shopper_can_log_out(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'))
            ->assertSessionHas(FlashMessage::TITLE, __('auth.logout.status'));

        $this->assertGuest();
    }

    public function test_guests_cannot_log_out(): void
    {
        $this->post(route('logout'))->assertRedirect(route('login'));
    }

    public function test_the_header_offers_guests_a_login_link(): void
    {
        $this
            ->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee(__('components.header.login'))
            ->assertSee('data-auth-open', false)
            ->assertDontSee('action="'.route('logout').'"', false);
    }

    public function test_the_header_offers_shoppers_an_account_chip_and_a_logout_form(): void
    {
        $user = User::factory()->create(['name' => 'Layla Hassan']);

        $this
            ->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(__('components.header.account'))
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee(__('components.header.logout'))
            ->assertDontSee('href="'.route('login').'"', false);
    }

    public function test_browsing_never_requires_authentication(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('products.index'))->assertOk();
        $this->get(route('category.index'))->assertOk();
        $this->get(route('cart.index'))->assertOk();
    }

    public function test_guests_can_still_use_the_cart_and_wishlist(): void
    {
        $product = $this->createProduct();

        $this
            ->asGuest()
            ->post(route('cart.add'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $this
            ->asGuest()
            ->post(route('wishlist.add'), ['product_id' => $product->id])
            ->assertRedirect();

        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id]);
        $this->assertDatabaseHas('wishlist_items', [
            'product_id' => $product->id,
            'session_id' => self::SESSION_ID,
        ]);
    }

    public function test_a_failed_login_from_the_dialog_does_not_mark_the_register_panel(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this
            ->asGuest()
            ->from(route('home'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrorsIn('login', 'email');

        $html = $this
            ->asGuest()
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-open="true"', false)
            ->getContent();

        // The register form sits beside the login form and shares its `email` and
        // `password` field names, so a login error must not surface in it.
        $registerPanel = $this->dialogPanel($html, 'auth-panel-register');

        static::assertStringNotContainsString('form-error', $registerPanel);
        static::assertStringNotContainsString('aria-invalid', $registerPanel);

        // ...while the login panel it belongs to shows it.
        $loginPanel = $this->dialogPanel($html, 'auth-panel-login');

        static::assertStringContainsString(
            'id="auth-login-email-error"',
            $loginPanel,
        );
        static::assertStringContainsString('aria-invalid="true"', $loginPanel);
    }

    public function test_the_page_and_the_dialog_render_the_same_login_form(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        $pageForm = $this->authFormMarkup($html, 'login');
        $dialogForm = $this->authFormMarkup($html, 'auth-login');

        static::assertNotSame('', $pageForm, 'The page renders no login form.');
        static::assertNotSame(
            '',
            $dialogForm,
            'The dialog renders no login form.',
        );

        // One component renders both, so the only differences are the id prefix
        // (the two copies share a page) and which copy takes focus.
        $normalise = static fn (string $form): string => (string) preg_replace(
            ['/auth-login-/', '/\s*(?:autofocus|data-auth-focus)="[^"]*"/'],
            ['login-', ''],
            $form,
        );

        static::assertSame($normalise($pageForm), $normalise($dialogForm));
    }

    /**
     * One panel of the auth dialog, from its heading to its closing tag.
     */
    private function dialogPanel(string $html, string $panelId): string
    {
        return Str::before(
            Str::after($html, 'id="'.$panelId.'"'),
            '</section>',
        );
    }

    /**
     * The auth form whose fields carry the given id prefix.
     */
    private function authFormMarkup(string $html, string $prefix): string
    {
        foreach (explode('<form class="auth-form', $html) as $chunk) {
            if (str_contains($chunk, 'id="'.$prefix.'-email"')) {
                return '<form class="auth-form'.Str::before($chunk, '</form>').'</form>';
            }
        }

        return '';
    }

    private function asGuest(): static
    {
        return $this->withCookie(config('session.cookie'), self::SESSION_ID);
    }

    /**
     * The cookie the "remember me" token is stored in.
     */
    private function recallerCookieName(): string
    {
        /** @var SessionGuard $guard */
        $guard = Auth::guard('web');

        return $guard->getRecallerName();
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
}
