<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The accessibility contract every storefront page keeps: one labelled set of
 * landmarks a keyboard can jump into, the language and direction the page is
 * read in, a field that says out loud why it was refused, and a design system
 * whose colours, focus ring and tap targets are shared tokens rather than
 * per-page choices.
 */
class AccessibilityTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * A fixed, valid session id (40 alphanumeric chars) so the requests within a
     * test share one session, and therefore one set of flashed errors. The test
     * client does not replay the session cookie automatically, so it is sent with
     * every request instead.
     */
    private const string SESSION_ID = 'AccessibilityTestSession012345678901234567';

    /**
     * The pages a shopper reaches without signing in.
     *
     * @return array<string, array{0: string}>
     */
    public static function publicPages(): array
    {
        return [
            'homepage' => ['home'],
            'category index' => ['category.index'],
            'product listing' => ['products.index'],
            'search results' => ['products.index'],
            'cart' => ['cart.index'],
            'contact' => ['contact.index'],
            'login' => ['login'],
            'register' => ['register'],
            'forgot password' => ['password.request'],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_a_page_gives_the_keyboard_one_labelled_set_of_landmarks_to_move_through(string $route): void
    {
        $html = $this->get(route($route))->assertOk()->getContent();

        // One page region, and the way into it: the skip link's target is the
        // same id the <main> carries.
        static::assertSame(1, substr_count($html, '<main'), "{$route} has more than one main region.");
        static::assertStringContainsString('id="main-content"', $html);
        static::assertStringContainsString(
            'class="site-skip-link" href="#main-content"',
            $html,
            "{$route} does not open with the way past the navigation.",
        );

        // The banner and the contentinfo the page sits between, with the
        // navigation named so a reader can tell it apart from other lists.
        static::assertStringContainsString('<header class="site-header">', $html);
        static::assertStringContainsString(
            'aria-label="'.__('components.header.navigation').'"',
            $html,
        );
        static::assertStringContainsString('<footer class="site-footer">', $html);

        // The document says which language it is read in, and in which
        // direction.
        static::assertStringContainsString('<html lang="en" dir="ltr"', $html);
    }

    public function test_the_signed_in_pages_keep_the_same_landmarks(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ([route('account.index'), route('orders.index')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            static::assertSame(1, substr_count($html, '<main'));
            static::assertStringContainsString('id="main-content"', $html);
            static::assertStringContainsString(
                'class="site-skip-link" href="#main-content"',
                $html,
            );
        }
    }

    public function test_a_page_rendered_in_arabic_is_read_the_other_way_round(): void
    {
        $this->withSession(['locale' => 'ar']);

        $this
            ->get(route('home'))
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false);

        $this
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false);
    }

    public function test_a_field_that_was_refused_says_so_to_a_screen_reader(): void
    {
        // The login form is refused the way a shopper refuses it: an address
        // that is not one, and no password.
        $this
            ->asGuest()
            ->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'not-an-address',
                'password' => 'secret',
            ])
            ->assertRedirect(route('login'));

        $html = $this
            ->asGuest()
            ->get(route('login'))
            ->assertOk()
            ->getContent();

        // The field is marked invalid, and described by the message printed
        // under it rather than only by what it is for.
        static::assertStringContainsString('id="login-email"', $html);
        static::assertStringContainsString('aria-invalid="true"', $html);
        static::assertStringContainsString(
            'aria-describedby="login-email-error"',
            $html,
        );
        static::assertStringContainsString('id="login-email-error"', $html);

        // The message is the refusal, printed rather than only announced.
        static::assertStringContainsString(
            'class="form-error"',
            $html,
        );
    }

    private function asGuest(): static
    {
        return $this->withCookie(config('session.cookie'), self::SESSION_ID);
    }

    public function test_the_storefront_paints_from_semantic_tokens_only(): void
    {
        $files = collect(File::allFiles(resource_path('css')))
            ->reject(static fn ($file) => str_contains(
                $file->getPathname(),
                'tokens',
            ))
            ->reject(static fn ($file) => $file->getFilename() === 'app.css');

        static::assertNotEmpty($files);

        foreach ($files as $file) {
            $css = file_get_contents($file->getPathname());
            $name = $file->getRelativePathname();

            // A component names a role, never a colour: a literal would be a
            // value the dark scheme and the palette cannot reach.
            static::assertDoesNotMatchRegularExpression(
                '/#[0-9a-fA-F]{3,8}\b/',
                $css,
                "{$name} paints with a colour literal.",
            );
            static::assertDoesNotMatchRegularExpression(
                '/\b(?:rgba?|hsla?|oklch)\(\s*[\d.]/',
                $css,
                "{$name} paints with a colour literal.",
            );
            // And it never reaches past the semantic layer for the palette step
            // underneath it.
            static::assertDoesNotMatchRegularExpression(
                '/var\(--(?:gray|primary|neutral|danger|success|warning|info)-\d/',
                $css,
                "{$name} reads a raw palette token instead of a semantic one.",
            );
        }
    }

    public function test_the_navigation_breakpoint_is_read_back_from_the_stylesheet(): void
    {
        $js = file_get_contents(
            resource_path('js/components/header.js'),
        );
        $css = file_get_contents(
            resource_path('css/components/site-header.css'),
        );

        // The stylesheet owns the number...
        preg_match('/--_nav-breakpoint: (\d+)px/', $css, $matches);

        $breakpoint = $matches[1] ?? null;

        static::assertNotNull(
            $breakpoint,
            'The stylesheet declares no nav breakpoint.',
        );

        // ...and repeats it only where it must: a media query is resolved
        // before custom properties are, so it cannot read the token itself.
        static::assertStringContainsString(
            '(max-width: '.$breakpoint.'px)',
            $css,
        );
        static::assertStringContainsString(
            '(min-width: '.($breakpoint + 1).'px)',
            $css,
        );

        // The script holds no copy of its own, so the two cannot drift: it
        // reads the token back and builds its query from it.
        static::assertStringNotContainsString('NAV_BREAKPOINT', $js);
        static::assertStringContainsString('--_nav-breakpoint', $js);
    }

    public function test_every_control_borrows_one_focus_ring_and_one_tap_target(): void
    {
        $semantic = file_get_contents(
            resource_path('css/tokens/semantic-colors.css'),
        );
        $tokens = file_get_contents(
            resource_path('css/tokens/design-tokens.css'),
        );
        $base = file_get_contents(resource_path('css/base.css'));
        $buttons = file_get_contents(
            resource_path('css/components/button.css'),
        );

        // The ring is a semantic token, given for both colour schemes...
        static::assertSame(2, substr_count($semantic, '--clr-focus-ring:'));

        // ...and everything the keyboard can reach draws it, from one rule in
        // the base layer rather than control by control. Whitespace is not the
        // contract here, the declarations are.
        $baseRules = preg_replace('/\s+/', ' ', $base);

        static::assertStringContainsString(
            ':focus-visible { outline: var(--border-width-sm) solid '
            .'var(--clr-focus-ring); outline-offset: var(--border-width-xs); }',
            $baseRules,
        );

        // The buttons draw that same ring rather than one mixed from the colour
        // they are painted with: a button ringed in its own fill is ringed in
        // itself, which says nothing.
        $buttonRules = preg_replace('/\s+/', ' ', $buttons);

        static::assertStringContainsString(
            '&:focus-visible { outline: var(--border-width-sm) solid '
            .'var(--clr-focus-ring); outline-offset: var(--border-width-xs); }',
            $buttonRules,
        );

        // One square for every icon-only control, and one touch size.
        static::assertStringContainsString('--size-icon-button: 2.5rem;', $tokens);
        static::assertStringContainsString('--size-touch-target: 2.75rem;', $tokens);
        static::assertStringContainsString(
            'inline-size: var(--size-icon-button);',
            $buttons,
        );
    }
}
