<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\FlashMessage;
use App\Models\Address;
use App\Models\Country;
use App\Models\Phone;
use App\Models\User;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * A fixed, valid session id (40 alphanumeric characters), so the requests
     * within a test share one session the way a browser's would — including the
     * messages a refused form comes back with. The test client does not replay
     * the session cookie automatically, so it is sent with every request
     * instead.
     */
    private const string SESSION_ID = 'AccountProfileSession0123456789012345678';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->withCookie(config('session.cookie'), self::SESSION_ID);

        // The password policy looks a breached password up in an external
        // service, so it is stubbed: the suite stays offline and the outcome is
        // the test's.
        $this->stubBreachCheck();
    }

    public function test_the_profile_shows_the_details_the_account_holds(): void
    {
        $country = Country::factory()->bahrain()->create();
        $user = $this->accountWithDetails($country);

        $this
            ->actingAs($user)
            ->get(route('account.edit'))
            ->assertOk()
            ->assertSee(__('account.profile.title'))
            ->assertSee('value="Layla Hassan"', false)
            ->assertSee('value="layla@example.com"', false)
            ->assertSee('value="39000000"', false)
            // The address card is answered with the address the checkout would
            // start from, field for field.
            ->assertSee(__('account.profile.address.title'))
            ->assertSee('value="12 Road 1234"', false)
            ->assertSee('value="Manama"', false)
            // And the password card asks for the current password.
            ->assertSee(__('account.profile.password.current'));
    }

    public function test_the_profile_opens_blank_for_an_account_that_holds_nothing(): void
    {
        $this
            ->actingAs(User::factory()->create())
            ->get(route('account.edit'))
            ->assertOk()
            ->assertSee(__('account.profile.address.hint'))
            ->assertDontSee('value="12 Road 1234"', false);
    }

    public function test_saving_the_details_writes_them_to_the_account(): void
    {
        $country = Country::factory()->bahrain()->create();
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->patch(
                route('account.update'),
                $this->details($user, [
                    'name' => 'Layla Hassan',
                    'email' => 'layla@example.com',
                    'locale' => 'ar',
                    'profile_country_id' => $country->id,
                    'phone_number' => '39000000',
                ]),
            )
            ->assertRedirect(route('account.edit'))
            ->assertSessionHas(FlashMessage::TITLE, __('account.profile.saved'));

        $user->refresh();

        static::assertSame('Layla Hassan', $user->name);
        static::assertSame('layla@example.com', $user->email);
        static::assertSame('ar', $user->locale);
        // The country is the account's own, and the number it gave is a phone
        // row of its own that names the same country.
        static::assertSame($country->id, $user->country_id);
        static::assertTrue($user->country->is($country));
        static::assertSame('39000000', $user->phone?->phone_number);
        static::assertSame($country->id, $user->phone->country_id);

        // The details form writes the details and nothing else: the address has
        // a form of its own.
        static::assertSame(0, Address::query()->count());

        // The language chosen here is the one the shopper is now shown the site
        // in, so the page they land back on agrees with it.
        static::assertSame('ar', session('locale'));
    }

    public function test_the_language_a_shopper_picks_is_the_one_they_are_shown(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)->patch(
            route('account.update'),
            $this->details(
                $user,
                ['locale' => 'ar'],
            ),
        );

        $this
            ->get(route('account.edit'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee(__('account.profile.title', locale: 'ar'));
    }

    public function test_an_email_another_account_holds_is_refused(): void
    {
        $user = User::factory()->create(['email' => 'layla@example.com']);
        User::factory()->create(['email' => 'someone@example.com']);

        $this
            ->actingAs($user)
            ->patch(
                route('account.update'),
                $this->details($user, ['email' => 'someone@example.com']),
            )
            ->assertSessionHasErrors('email');

        static::assertSame('layla@example.com', $user->refresh()->email);

        // The account's own address is not a duplicate of itself.
        $this
            ->actingAs($user)
            ->patch(route('account.update'), $this->details($user))
            ->assertSessionHasNoErrors();
    }

    public function test_the_phone_must_fit_the_country_it_was_given_for(): void
    {
        // Bahrain's numbers are eight digits long (countries.phone_length).
        $country = Country::factory()->bahrain()->create();
        $user = User::factory()->create();

        // Too few digits, and a number that is not digits at all: both are
        // refused, and neither is written.
        foreach (['3900', '+973 39000000'] as $number) {
            $this
                ->actingAs($user)
                ->patch(
                    route('account.update'),
                    $this->details(
                        $user,
                        [
                            'profile_country_id' => $country->id,
                            'phone_number' => $number,
                        ],
                    ),
                )
                ->assertSessionHasErrors('phone_number');

            static::assertNull($user->refresh()->phone_id);
        }

        // A number that fits the country is kept as it was given.
        $this
            ->actingAs($user)
            ->patch(
                route('account.update'),
                $this->details(
                    $user,
                    [
                        'profile_country_id' => $country->id,
                        'phone_number' => '39000000',
                    ],
                ),
            )
            ->assertSessionHasNoErrors();

        static::assertSame('39000000', $user->refresh()->phone->phone_number);
    }

    public function test_a_refused_number_names_the_field_the_way_the_page_does(): void
    {
        $country = Country::factory()->bahrain()->create();
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->post(route('lang'), ['language' => 'ar']);

        foreach (['3900', '+973 39000000'] as $number) {
            $this->patch(
                route('account.update'),
                $this->details($user, [
                    'profile_country_id' => $country->id,
                    'phone_number' => $number,
                ]),
            )->assertSessionHasErrors('phone_number');

            $message = session('errors')->first('phone_number');

            static::assertStringContainsString(
                __('validation.attributes.phone_number', locale: 'ar'),
                $message,
            );

            static::assertStringNotContainsString('phone_number', $message);
        }
    }

    public function test_a_number_needs_its_country_but_a_country_stands_alone(): void
    {
        $country = Country::factory()->bahrain()->create();
        $user = User::factory()->create();

        // A number without its country cannot be checked against a numbering
        // plan, so it is refused rather than stored half-given.
        $this
            ->actingAs($user)
            ->patch(
                route('account.update'),
                $this->details($user, ['phone_number' => '39000000']),
            )
            ->assertSessionHasErrors('profile_country_id');

        static::assertNull($user->refresh()->phone_id);
        static::assertNull($user->country_id);

        // A country on its own is the account's own answer: the number is the
        // shopper's to leave out, and the country is kept either way.
        $this
            ->actingAs($user)
            ->patch(
                route('account.update'),
                $this->details($user, [
                    'profile_country_id' => $country->id,
                ]),
            )
            ->assertSessionHasNoErrors()
            ->assertSessionHas(FlashMessage::TITLE);

        $user->refresh();

        static::assertSame($country->id, $user->country_id);
        static::assertNull($user->phone_id);
        static::assertSame(0, Phone::query()->count());

        // The details a shopper may leave out entirely.
        $this
            ->actingAs($user)
            ->patch(route('account.update'), $this->details($user))
            ->assertSessionHasNoErrors();

        static::assertNull($user->refresh()->country_id);
    }

    public function test_a_saved_country_is_the_one_the_form_opens_on(): void
    {
        $bahrain = Country::factory()->bahrain()->create();
        Country::factory()->create(['name_en' => 'Qatar', 'calling_code' => '974']);

        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->patch(
                route('account.update'),
                $this->details($user, [
                    'profile_country_id' => $bahrain->id,
                    'phone_number' => '39000000',
                ]),
            )
            ->assertSessionHas(FlashMessage::TITLE);

        // What was saved is what the form comes back with. The account's country
        // is its own, and the phone it gave names the same one.
        $user->refresh();

        static::assertSame($bahrain->id, $user->country_id);
        static::assertSame($bahrain->id, $user->phone?->country_id);

        $html = $this->get(route('account.edit'))->assertOk()->getContent();

        preg_match(
            '/<select name="profile_country_id".*?<\/select>/s',
            $html,
            $matches,
        );

        static::assertNotSame('', $matches[0] ?? '');
        static::assertSame(1, substr_count($matches[0], ' selected'));
        static::assertMatchesRegularExpression(
            '/value="'.$bahrain->id.'"\s+selected/',
            $matches[0],
        );
    }

    public function test_the_phone_is_a_row_of_its_own_and_is_shared(): void
    {
        $country = Country::factory()->bahrain()->create();
        $first = User::factory()->create();
        $second = User::factory()->create();

        foreach ([$first, $second] as $user) {
            $this->actingAs($user)->patch(
                route('account.update'),
                $this->details(
                    $user,
                    [
                        'profile_country_id' => $country->id,
                        'phone_number' => '39000000',
                    ],
                ),
            );
        }

        // One number, one row, both accounts linked to it.
        static::assertSame(1, Phone::query()->count());
        static::assertTrue($first->refresh()->phone->is($second->refresh()->phone));
    }

    public function test_clearing_the_phone_unlinks_it(): void
    {
        $country = Country::factory()->bahrain()->create();
        $user = $this->accountWithDetails($country);

        $this->actingAs($user)->patch(
            route('account.update'),
            $this->details(
                $user,
                ['profile_country_id' => null, 'phone_number' => null],
            ),
        );

        static::assertNull($user->refresh()->phone_id);
    }

    public function test_the_address_form_saves_the_address_the_orders_go_to(): void
    {
        $country = Country::factory()->bahrain()->create();
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->patch(route('account.address.update'), $this->address($country))
            ->assertRedirect(route('account.edit'))
            ->assertSessionHas(FlashMessage::TITLE, __('account.profile.address.saved'));

        $address = $user->addresses()->sole();

        static::assertSame('12 Road 1234', $address->street_address);
        static::assertSame('Manama', $address->city);
        static::assertTrue($address->country->is($country));

        // Answered again after moving house: the same row, written over.
        $this->actingAs($user)->patch(
            route('account.address.update'),
            $this->address(
                $country,
                ['street_address' => '5 Road 6789', 'city' => 'Riffa'],
            ),
        );

        static::assertSame(1, Address::query()->count());
        static::assertSame('5 Road 6789', $address->refresh()->street_address);
    }

    public function test_the_address_form_needs_the_whole_address(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->patch(route('account.address.update'), ['city' => 'Manama'])
            ->assertSessionHasErrors([
                'street_address',
                'state',
                'country_id',
                'postal_code',
            ]);

        static::assertSame(0, Address::query()->count());
    }

    public function test_the_details_form_leaves_the_address_on_file_alone(): void
    {
        $country = Country::factory()->bahrain()->create();
        $user = $this->accountWithDetails($country);
        $address = $user->addresses()->sole();

        $this->actingAs($user)->patch(
            route('account.update'),
            $this->details(
                $user,
                ['name' => 'Layla H.'],
            ),
        );

        static::assertSame('12 Road 1234', $address->refresh()->street_address);
        static::assertSame(1, Address::query()->count());
    }

    public function test_a_refused_form_comes_back_open_on_the_field_that_needs_answering(): void
    {
        $user = User::factory()->create(['email' => 'layla@example.com']);
        User::factory()->create(['email' => 'someone@example.com']);

        $this
            ->actingAs($user)
            ->from(route('account.edit'))
            ->patch(
                route('account.update'),
                $this->details($user, [
                    'name' => 'Layla H.',
                    'email' => 'someone@example.com',
                ]),
            )
            ->assertSessionHasErrors('email');

        $this
            ->get(route('account.edit'))
            ->assertOk()
            // What the shopper typed is still there to be corrected...
            ->assertSee('value="someone@example.com"', false)
            ->assertSee('value="Layla H."', false)
            // ...and the field they have to answer is the one the script opens
            // the form on and focuses (see account-form.js).
            ->assertSee('aria-invalid="true"', false);
    }

    public function test_a_refused_field_belongs_to_one_form_only(): void
    {
        $user = User::factory()->create();

        // A number without its country refuses the details form's own country
        // field — the address form beside it has a country select too, and the
        // two must not answer for each other.
        $this
            ->actingAs($user)
            ->from(route('account.edit'))
            ->patch(
                route('account.update'),
                $this->details($user, [
                    'phone_number' => '39000000',
                ]),
            )
            ->assertSessionHasErrors('profile_country_id');

        $html = $this->get(route('account.edit'))->assertOk()->getContent();

        // Both selects are on the page, and exactly one of them is the refused
        // field — the one whose name the request validated.
        static::assertStringContainsString('id="field-profile_country_id"', $html);
        static::assertStringContainsString('id="field-country_id"', $html);
        static::assertMatchesRegularExpression(
            '/id="field-profile_country_id"[^>]*aria-invalid="true"/',
            $html,
        );
        static::assertDoesNotMatchRegularExpression(
            '/id="field-country_id"[^>]*aria-invalid="true"/',
            $html,
        );
        static::assertSame(1, substr_count($html, 'aria-invalid="true"'));
    }

    public function test_an_address_refused_leaves_the_phone_fields_alone(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->from(route('account.edit'))
            ->patch(route('account.address.update'), ['city' => 'Manama'])
            ->assertSessionHasErrors('country_id');

        $html = $this->get(route('account.edit'))->assertOk()->getContent();

        static::assertMatchesRegularExpression(
            '/id="field-country_id"[^>]*aria-invalid="true"/',
            $html,
        );
        static::assertDoesNotMatchRegularExpression(
            '/id="field-profile_country_id"[^>]*aria-invalid="true"/',
            $html,
        );
    }

    public function test_the_password_change_needs_the_current_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this
            ->actingAs($user)
            ->from(route('account.edit'))
            ->put(route('account.password.update'), [
                'current_password' => 'not-the-password',
                'password' => 'new-Password-1',
                'password_confirmation' => 'new-Password-1',
            ])
            ->assertRedirect(route('account.edit'))
            ->assertSessionHasErrors('current_password');

        static::assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_the_password_change_writes_the_new_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this
            ->actingAs($user)
            ->put(route('account.password.update'), [
                'current_password' => 'password',
                'password' => 'new-Password-1',
                'password_confirmation' => 'new-Password-1',
            ])
            ->assertRedirect(route('account.edit'))
            ->assertSessionHas(FlashMessage::TITLE, __('account.profile.password_saved'));

        $user->refresh();

        static::assertTrue(Hash::check('new-Password-1', $user->password));
        // The account is still the one signed in, with the password it now has.
        static::assertTrue(auth()->attempt([
            'email' => $user->email,
            'password' => 'new-Password-1',
        ]));
    }

    public function test_the_new_password_must_confirm_and_pass_the_policy(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this
            ->actingAs($user)
            ->from(route('account.edit'))
            ->put(route('account.password.update'), [
                'current_password' => 'password',
                'password' => 'new-Password-1',
                'password_confirmation' => 'something-else',
            ])
            ->assertSessionHasErrors('password');

        $this
            ->actingAs($user)
            ->from(route('account.edit'))
            ->put(route('account.password.update'), [
                'current_password' => 'password',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');

        static::assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_the_profile_hands_each_form_to_the_script_that_unlocks_it(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('account.edit'))->assertOk();

        $html = $response->getContent();

        // One form per thing the profile changes, each with the controls the
        // script binds to. The form's own hook is a bare attribute, so it is the
        // one match that is not followed by another attribute's `-`.
        static::assertSame(3, preg_match_all('/data-account-form(?!-)/', $html));
        static::assertSame(3, substr_count($html, 'data-account-form-toggle='));
        static::assertSame(3, substr_count($html, 'data-account-form-cancel='));

        // The password's card holds a secret: it is the one form that shows no
        // fields while it is locked, because a masked empty field says nothing.
        static::assertSame(1, substr_count($html, 'account-form--secrets'));

        // Both controls stay out of the way where there is no script, because
        // without it there is nothing to unlock — and the fields are never
        // rendered read-only, so a shopper without scripting can still save them.
        static::assertSame(3, substr_count($html, 'account-form__toggle js-only'));
        static::assertSame(
            3,
            substr_count($html, 'js-only" data-account-form-cancel'),
        );
        $response->assertDontSee('disabled="disabled"', false);

        // The lock itself is the script's, and the states it toggles are the
        // stylesheet's.
        static::assertStringContainsString(
            './components/account-form',
            file_get_contents(resource_path('js/app.js')),
        );

        $css = file_get_contents(resource_path('css/features/account.css'));

        static::assertStringContainsString(
            '.account-form--locked .account-form__actions',
            $css,
        );
        static::assertStringContainsString(
            '.account-form--editing .account-form__toggle',
            $css,
        );
        static::assertStringContainsString(
            '.account-form--secrets.account-form--locked > .form-field',
            $css,
        );
    }

    public function test_the_profile_renders_in_arabic_and_mirrors_the_layout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        // Switch the shopper to Arabic the way the header's control does, so the
        // page is rendered through the locale middleware.
        $this->post(route('lang'), ['language' => 'ar']);

        $this
            ->get(route('account.edit'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee(__('account.profile.title', locale: 'ar'))
            ->assertSee(__('account.profile.password.current', locale: 'ar'));
    }

    public function test_every_country_the_selects_offer_carries_its_arabic_name(): void
    {
        Country::factory()->bahrain()->create();
        Country::factory()->create([
            'name_en' => 'United Arab Emirates',
            'name_ar' => 'الإمارات العربية المتحدة',
            'calling_code' => '971',
        ]);

        $this->actingAs(User::factory()->create());

        $this->post(route('lang'), ['language' => 'ar']);

        $html = $this->get(route('account.edit'))->assertOk()->getContent();

        // Both of the page's country selects — the details form's own and the
        // address form's — are rendered here, and each has to name every country
        // it offers: an option with no label is an empty row in the drop-down.
        preg_match_all(
            '/<select name="(?:profile_country_id|country_id)".*?<\/select>/s',
            $html,
            $selects,
        );

        static::assertCount(2, $selects[0]);

        foreach ($selects[0] as $select) {
            static::assertStringContainsString('بحرين', $select);
            static::assertStringContainsString('الإمارات العربية المتحدة', $select);

            // Nothing offered is left without a name, whether or not it carries
            // the calling code beside it.
            static::assertDoesNotMatchRegularExpression(
                '/<option value="\d+"[^>]*>\s*(?:\(\+\d+\))?\s*<\/option>/',
                $select,
            );
        }
    }

    public function test_a_country_select_tells_its_state_apart_from_the_colours_its_drop_down_is_painted_from(): void
    {
        // A drop-down's list is painted from the field it belongs to: the list
        // wears the field's background and labels its options in the field's
        // colour. A field whose colours are mixed for a state is therefore a list
        // whose labels are never drawn (Firefox), so the state of a select is
        // told by its outline, and the rows are drawn on the field's own surface
        // rather than on a block of the state's colour.
        $css = file_get_contents(resource_path('css/base.css'));

        static::assertMatchesRegularExpression(
            '/select \{\s*&:user-valid \{\s*outline: var\(--border-width-xs\) solid\s+'
            .'var\(--clr-status-success-border\);\s*\}\s*'
            .'&:user-invalid \{\s*outline: var\(--border-width-xs\) solid\s+'
            .'var\(--clr-status-danger-border\);\s*\}\s*\}/',
            $css,
        );

        static::assertMatchesRegularExpression(
            '/option \{\s*color: var\(--clr-text-primary\);\s*background-color: var\(--_clr-surface\);\s*\}/',
            $css,
        );
    }

    public function test_the_details_form_keeps_a_country_and_its_number_on_one_row(): void
    {
        $this->actingAs(User::factory()->create());

        $html = $this->get(route('account.edit'))->assertOk()->getContent();

        preg_match(
            '/<div class="([^"]*account-form__pair[^"]*)">(.*?)<\/div>\s*<div class="form-actions/s',
            $html,
            $pair,
        );

        static::assertNotEmpty($pair, 'The country and the phone field are not paired.');

        $classes = preg_split('/\s+/', trim($pair[1]));

        if ($classes === false) {
            $classes = [];
        }

        sort($classes);

        // The wrapper is what pairs the two fields and the split grid is what
        // lays the pair out in one row. Which order the classes are written in
        // is the formatter's business, so they are asserted as a set.
        static::assertSame(
            ['account-form__pair', 'grid', 'grid-split'],
            $classes,
        );

        static::assertStringContainsString('name="profile_country_id"', $pair[2]);
        static::assertStringContainsString('name="phone_number"', $pair[2]);

        $css = file_get_contents(resource_path('css/utilities.css'));
        static::assertMatchesRegularExpression(
            '/\.grid-split \{\s*grid-template-columns: minmax\(auto, 1fr\);\s*\}/',
            $css,
        );

        static::assertMatchesRegularExpression(
            '/@media \(min-width: 600px\) \{\s*\.grid-split \{\s*'
            .'grid-template-columns: repeat\(2, minmax\(auto, 1fr\)\);\s*\}\s*\}/',
            $css,
        );
    }

    private function details(User $user, array $overrides = []): array
    {
        return array_merge([
            'name' => $user->name,
            'email' => $user->email,
            'locale' => 'en',
            'profile_country_id' => null,
            'phone_number' => null,
        ], $overrides);
    }

    /**
     * An address as the address form posts it.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function address(Country $country, array $overrides = []): array
    {
        return array_merge([
            'street_address' => '12 Road 1234',
            'city' => 'Manama',
            'state' => 'Capital',
            'country_id' => $country->id,
            'postal_code' => '1234',
        ], $overrides);
    }

    /**
     * An account with everything the profile holds: a name, an address, a phone
     * and a password.
     */
    private function accountWithDetails(Country $country): User
    {
        $phone = Phone::factory()->create([
            'country_id' => $country->id,
            'phone_number' => '39000000',
        ]);

        $user = User::factory()->create([
            'name' => 'Layla Hassan',
            'email' => 'layla@example.com',
            'password' => 'password',
            'phone_id' => $phone->id,
        ]);

        $user->addresses()->create([
            'address_type' => 'shipping',
            'street_address' => '12 Road 1234',
            'city' => 'Manama',
            'state' => 'Capital',
            'country_id' => $country->id,
            'postal_code' => '1234',
        ]);

        return $user;
    }

    /**
     * Replace the breach lookup with a stub, so the suite never calls out.
     */
    private function stubBreachCheck(): void
    {
        $this->app->bind(
            UncompromisedVerifier::class,
            static fn (): UncompromisedVerifier => new class implements UncompromisedVerifier
            {
                #[\Override]
                public function verify($data): bool
                {
                    return true;
                }
            },
        );
    }
}
