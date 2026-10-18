<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\FlashMessage;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * A fixed, valid session id (40 alphanumeric chars) so guest requests within
     * a test share one session. The test client does not replay the session
     * cookie automatically, so it is sent with every request instead.
     */
    private const string SESSION_ID = 'RegistrationTestSession12345678901234567';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // The breach lookup reaches an external service, so it is stubbed: the
        // suite stays offline and the policy stays deterministic.
        $this->stubBreachCheck(compromised: false);
    }

    public function test_guests_see_the_registration_page(): void
    {
        $this
            ->get(route('register'))
            ->assertOk()
            ->assertSee('action="'.route('register.store').'"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee(__('auth.register.subtitle'))
            ->assertSee(__('auth.register.alt'))
            // The card is centred in the middle of the page, not under the nav.
            ->assertSee('class="auth-page wrapper grid-center"', false)
            ->assertSee(route('login'));
    }

    public function test_the_registration_page_describes_the_rules_the_server_enforces(): void
    {
        $rule = Password::default();
        $applied = $rule->appliedRules();

        $this
            ->get(route('register'))
            ->assertOk()
            // The browser contract comes off the same rule object the request
            // validates with...
            ->assertSee(
                'passwordrules="'.$rule->toPasswordRulesString().'"',
                false,
            )
            ->assertSee('minlength: '.$applied['min'], false)
            ->assertSee('required: lower', false)
            ->assertSee('required: digit', false)
            // ...and so does every hint a shopper can act on.
            ->assertSee('id="register-password-rules"', false)
            ->assertSee(
                __('auth.password_rules.min', ['count' => $applied['min']]),
            )
            ->assertSee(__('auth.password_rules.letters'))
            ->assertSee(__('auth.password_rules.numbers'))
            ->assertSee(__('auth.password_rules.uncompromised'));
    }

    public function test_the_password_field_carries_the_rule_as_native_constraints(): void
    {
        $applied = Password::default()->appliedRules();

        $this
            ->get(route('register'))
            ->assertOk()
            // Length is expressed exactly...
            ->assertSee('minlength="'.$applied['min'].'"', false)
            ->assertSee('maxlength="'.$applied['max'].'"', false)
            // ...and each character requirement becomes a lookahead, so the
            // browser blocks submit — and stops calling the field valid — for
            // the same reasons the server would refuse it.
            ->assertSee('pattern="(?=.*\p{L})(?=.*\p{N}).*"', false);
    }

    public function test_the_browser_is_given_the_rule_and_the_messages_the_server_would_return(): void
    {
        $applied = Password::default()->appliedRules();
        $html = $this->get(route('register'))->assertOk()->getContent();

        preg_match('/data-password-policy="([^"]*)"/', $html, $matches);

        $policy = json_decode(html_entity_decode($matches[1] ?? ''), true);

        static::assertIsArray(
            $policy,
            'The password field carries no parseable policy.',
        );

        // The script reads the rule itself off the field...
        static::assertSame($applied['min'], $policy['min']);
        static::assertSame($applied['max'], $policy['max']);
        static::assertSame($applied['letters'], $policy['letters']);
        static::assertSame($applied['numbers'], $policy['numbers']);
        static::assertSame($applied['symbols'], $policy['symbols']);
        static::assertSame($applied['mixedCase'], $policy['mixedCase']);

        // ...and the messages it sets on the field, which are the very lang
        // lines the validator composes its own errors from.
        $attribute = __('validation.attributes.password');

        static::assertSame(
            [
                'min' => __('validation.min.string', [
                    'attribute' => $attribute,
                ]),
                'max' => __('validation.max.string', [
                    'attribute' => $attribute,
                ]),
                'mixedCase' => __('validation.password.mixed', [
                    'attribute' => $attribute,
                ]),
                'letters' => __('validation.password.letters', [
                    'attribute' => $attribute,
                ]),
                'numbers' => __('validation.password.numbers', [
                    'attribute' => $attribute,
                ]),
                'symbols' => __('validation.password.symbols', [
                    'attribute' => $attribute,
                ]),
            ],
            $policy['messages'],
        );
    }

    public function test_the_password_policy_is_mirrored_into_the_fields_validity(): void
    {
        $this
            ->get(route('register'))
            ->assertOk()
            ->assertSee('data-password-policy="', false);

        $script = file_get_contents(
            resource_path('js/components/password-policy.js'),
        );

        static::assertStringContainsString('data-password-policy', $script);
        static::assertStringContainsString('setCustomValidity', $script);

        static::assertStringContainsString(
            './components/password-policy',
            file_get_contents(resource_path('js/app.js')),
        );
    }

    public function test_the_password_field_is_described_by_its_rules_and_its_error(): void
    {
        $this
            ->asGuest()
            ->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Layla Hassan',
                'email' => 'layla@example.com',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertRedirect(route('register'));

        $this
            ->asGuest()
            ->get(route('register'))
            ->assertOk()
            ->assertSee(
                'aria-describedby="register-password-rules register-password-error"',
                false,
            )
            ->assertSee('id="register-password-error"', false)
            ->assertSee('aria-invalid="true"', false);
    }

    public function test_the_page_and_the_dialog_render_the_same_register_form(): void
    {
        $html = $this->get(route('register'))->assertOk()->getContent();

        $pageForm = $this->registerFormMarkup($html, 'register');
        $dialogForm = $this->registerFormMarkup($html, 'auth-register');

        static::assertNotSame(
            '',
            $pageForm,
            'The page renders no register form.',
        );
        static::assertNotSame(
            '',
            $dialogForm,
            'The dialog renders no register form.',
        );

        // One component renders both, so the only differences are the id prefix
        // (the two copies share a page) and which copy owns focus.
        $normalise = static fn (string $form): string => (string) preg_replace(
            ['/auth-register-/', '/\s*autofocus="autofocus"/'],
            ['register-', ''],
            $form,
        );

        static::assertSame($normalise($pageForm), $normalise($dialogForm));
    }

    public function test_a_failed_registration_from_the_dialog_marks_the_register_panel_only(): void
    {
        $this
            ->asGuest()
            ->from(route('home'))
            ->post(route('register.store'), [
                'name' => 'Layla Hassan',
                'email' => 'not-an-email',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrorsIn('register', 'email');

        $html = $this
            ->asGuest()
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-open="true"', false)
            ->getContent();

        // The dialog reopens on the register panel's own error...
        static::assertStringContainsString(
            'id="auth-register-email-error"',
            $html,
        );

        // ...while the login panel beside it stays clean, even though both forms
        // are built from fields with the same names.
        $loginPanel = $this->dialogPanel($html, 'auth-panel-login');

        static::assertStringNotContainsString('form-error', $loginPanel);
        static::assertStringNotContainsString('aria-invalid', $loginPanel);
    }

    public function test_signed_in_shoppers_are_sent_home_from_the_registration_page(): void
    {
        $this
            ->actingAs(User::factory()->create())
            ->get(route('register'))
            ->assertRedirect(route('home'));
    }

    public function test_the_registration_page_renders_in_arabic_and_mirrors_the_layout(): void
    {
        $this
            ->withSession(['locale' => 'ar'])
            ->get(route('register'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee(__('auth.register.title', locale: 'ar'), false)
            ->assertSee(__('components.auth.name', locale: 'ar'), false);
    }

    public function test_a_guest_can_register(): void
    {
        $this
            ->post(route('register.store'), [
                'name' => 'Layla Hassan',
                'email' => 'layla@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHas(FlashMessage::TITLE, __('auth.register.status'));

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'name' => 'Layla Hassan',
            'email' => 'layla@example.com',
            'role' => UserRole::CUSTOMER->value,
            'status' => UserStatus::ACTIVE->value,
            'locale' => 'en',
        ]);
    }

    public function test_registration_validates_the_input(): void
    {
        $this
            ->from(route('register'))
            ->post(route('register.store'), [])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrorsIn('register', [
                'name',
                'email',
                'password',
            ]);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_an_email_that_is_already_taken(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this
            ->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Layla Hassan',
                'email' => 'taken@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrorsIn('register', 'email');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_rejects_a_confirmation_that_does_not_match(): void
    {
        $this
            ->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Layla Hassan',
                'email' => 'layla@example.com',
                'password' => 'password123',
                'password_confirmation' => 'something-else',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrorsIn('register', 'password');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_requires_a_password_of_at_least_eight_characters(): void
    {
        $this
            ->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Layla Hassan',
                'email' => 'layla@example.com',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrorsIn('register', 'password');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_enforces_the_letter_and_number_the_hint_promises(): void
    {
        // Letters only: long enough, but the hint says a number is required.
        $this
            ->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Layla Hassan',
                'email' => 'layla@example.com',
                'password' => 'qzxwvtrnmol',
                'password_confirmation' => 'qzxwvtrnmol',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrorsIn('register', 'password');

        // Numbers only: the mirror case, the hint says a letter is required.
        $this
            ->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Layla Hassan',
                'email' => 'layla@example.com',
                'password' => '123456789012',
                'password_confirmation' => '123456789012',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrorsIn('register', 'password');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_a_password_found_in_a_data_breach(): void
    {
        $this->stubBreachCheck(compromised: true);

        $this
            ->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Layla Hassan',
                'email' => 'layla@example.com',
                'password' => 'a-breached-password-123',
                'password_confirmation' => 'a-breached-password-123',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrorsIn('register', 'password');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_carries_the_guest_cart_and_wishlist_onto_the_account(): void
    {
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
            ->post(route('register.store'), [
                'name' => 'Layla Hassan',
                'email' => 'layla@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('home'));

        $user = User::query()
            ->where('email', 'layla@example.com')
            ->firstOrFail();

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

    public function test_the_forgot_password_page_explains_the_placeholder(): void
    {
        $this
            ->get(route('password.request'))
            ->assertOk()
            ->assertSee(__('auth.forgot.message'))
            ->assertSee(__('auth.forgot.back'))
            ->assertSee(route('login'));
    }

    public function test_validation_errors_are_translated_in_arabic(): void
    {
        $response = $this
            ->withSession(['locale' => 'ar'])
            ->from(route('register'))
            ->post(route('register.store'), [
                'name' => '',
                'email' => 'not-an-email',
                'password' => 'short',
                'password_confirmation' => 'mismatch',
            ]);

        $response->assertRedirect(route('register'));

        // Literal Arabic: a translation missing from lang/ar/validation.php would
        // fall back to English through the locale chain and fail these.
        $response
            ->assertSessionHasErrorsIn('register', [
                'name' => 'حقل الاسم الكامل مطلوب.',
            ])
            ->assertSessionHasErrorsIn('register', [
                'email' => 'يجب أن يكون حقل البريد الإلكتروني بريداً إلكترونياً صالحاً.',
            ])
            ->assertSessionHasErrorsIn('register', [
                'password' => 'تأكيد حقل كلمة المرور غير مطابق.',
            ])
            ->assertSessionHasErrorsIn('register', [
                'password' => 'يجب أن يكون طول نص كلمة المرور 8 حرفاً على الأقل.',
            ]);
    }

    public function test_the_password_hints_and_browser_messages_are_translated_in_arabic(): void
    {
        $this
            ->withSession(['locale' => 'ar'])
            ->get(route('register'))
            ->assertOk()
            ->assertSee(
                __('auth.password_rules.min', ['count' => 8], 'ar'),
                false,
            )
            ->assertSee(__('auth.password_rules.letters', locale: 'ar'), false)
            ->assertSee(__('auth.password_rules.numbers', locale: 'ar'), false)
            ->assertSee(
                __('auth.password_rules.uncompromised', locale: 'ar'),
                false,
            )
            // The message the browser will show is the Arabic one too, not the
            // browser's own untranslated complaint.
            ->assertSee(
                __(
                    'validation.min.string',
                    [
                        'attribute' => __(
                            'validation.attributes.password',
                            locale: 'ar',
                        ),
                    ],
                    'ar',
                ),
                false,
            );
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
     * The register form whose fields carry the given id prefix.
     */
    private function registerFormMarkup(string $html, string $prefix): string
    {
        foreach (explode('<form class="auth-form', $html) as $chunk) {
            if (str_contains($chunk, 'id="'.$prefix.'-name"')) {
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
     * Replace the breach lookup with a stub, so the suite never calls out and
     * the outcome is chosen by the test.
     */
    private function stubBreachCheck(bool $compromised): void
    {
        $this->app->bind(
            UncompromisedVerifier::class,
            static fn (): UncompromisedVerifier => new class($compromised) implements UncompromisedVerifier
            {
                public function __construct(
                    private bool $compromised,
                ) {}

                #[\Override]
                public function verify($data): bool
                {
                    return ! $this->compromised;
                }
            },
        );
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
