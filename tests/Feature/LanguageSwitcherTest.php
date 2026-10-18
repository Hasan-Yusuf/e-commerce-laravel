<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LanguageSwitcherTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_signed_in_shoppers_choice_is_kept_on_their_account(): void
    {
        $user = User::factory()->create([
            'name' => 'Layla Hassan',
            'locale' => 'en',
        ]);

        $this
            ->actingAs($user)
            ->from(route('home'))
            ->post(route('lang'), ['language' => 'ar'])
            ->assertRedirect(route('home'));

        // The session is the language being read now, and the account keeps it
        // for the next visit — the shopper's other details untouched.
        static::assertSame('ar', session('locale'));

        $user->refresh();

        static::assertSame('ar', $user->locale);
        static::assertSame('ar', $user->preferredLocale());
        static::assertSame('Layla Hassan', $user->name);

        // ...and the account area reads the choice back.
        $this
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee(__('account.details.language'))
            ->assertSee(__('languages.arabic'));
    }

    public function test_a_guest_keeps_their_choice_in_the_session_only(): void
    {
        $this
            ->from(route('home'))
            ->post(route('lang'), ['language' => 'ar'])
            ->assertRedirect(route('home'));

        static::assertSame('ar', session('locale'));
        $this->assertGuest();

        // Nothing of the storefront is left in the other language: the guest is
        // simply reading it in Arabic.
        $this
            ->get(route('home'))
            ->assertOk()
            ->assertSee('dir="rtl"', false);
    }

    public function test_a_language_the_store_does_not_offer_is_refused(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this
            ->actingAs($user)
            ->from(route('home'))
            ->post(route('lang'), ['language' => 'nonsense'])
            ->assertSessionHasErrors('language');

        // Neither the session nor the account was changed by a language that is
        // not offered.
        static::assertNotSame('nonsense', session('locale'));
        static::assertSame('en', $user->refresh()->locale);
    }
}
