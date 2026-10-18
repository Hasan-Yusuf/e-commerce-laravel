<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HeaderNavigationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_navigation_renders_burger_button_that_controls_the_drawer(): void
    {
        $this
            ->get(route('home'))
            ->assertOk()
            ->assertSee('class="site-header__burger"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('aria-controls="site-nav"', false)
            ->assertSee('id="site-nav"', false);
    }

    public function test_search_sits_at_the_end_of_the_navigation_bar(): void
    {
        $response = $this->get(route('home'))->assertOk();

        $nav = $this->extractNavHtml($response);

        // The search moved out of the top bar and onto the end of the bar,
        // after the category list.
        static::assertStringContainsString('site-header__nav-bar', $nav);
        static::assertStringContainsString(
            'class="site-header__search site-header__search--nav"',
            $nav,
        );
        static::assertStringContainsString('id="search"', $nav);
        static::assertGreaterThan(
            strpos($nav, 'site-header__nav-list'),
            strpos($nav, 'site-header__search--nav'),
        );

        // The header itself only keeps the small-screen search row, so the
        // top bar no longer carries a search of its own.
        $header = $this->extractBetween(
            $response->getContent(),
            '<header class="site-header">',
            '<nav class="site-header__nav"',
        );

        static::assertStringContainsString('id="search-mobile"', $header);
        static::assertStringNotContainsString('id="search"', $header);
    }

    public function test_navigation_sticks_to_the_top_of_the_viewport(): void
    {
        $css = file_get_contents(
            resource_path('css/components/site-header.css'),
        );

        // Pinned on desktop...
        static::assertMatchesRegularExpression(
            '/\.site-header__nav \{\s*position: sticky;\s*inset-block-start: 0;/',
            $css,
        );

        // ...and still a fixed off-canvas drawer below the breakpoint.
        static::assertMatchesRegularExpression(
            '/\.site-header__nav \{[\s\S]*?@media \(max-width: 1160px\) \{\s*position: fixed;/',
            $css,
        );

        // A sticky element only travels inside its parent's box, so the nav
        // must sit outside (after) the <header> element…
        $html = $this->get(route('home'))->assertOk()->getContent();

        static::assertLessThan(
            strpos($html, '<nav class="site-header__nav"'),
            strpos($html, '</header>'),
        );

        // …inside the header band, and the band is what reaches over the page's
        // row: without that the nav would only travel inside its own line. The
        // band's rows are the page grid's own (subgrid), which is what keeps the
        // top bar and the nav in one row of the page — a band that sits in a
        // single row of its own collapses that row and the page rides up over
        // the top bar.
        static::assertMatchesRegularExpression(
            '/\.site-header-band \{[\s\S]*?grid-row: 1 \/ 5;[\s\S]*?grid-template-rows: subgrid;/',
            file_get_contents(resource_path('css/components/layout.css')),
        );
    }

    /**
     * The HTML between two markers (used to look at one region of the page).
     */
    private function extractBetween(
        string $html,
        string $from,
        string $to,
    ): string {
        $start = strpos($html, $from);
        $end = strpos($html, $to);

        if ($start === false || $end === false || $end <= $start) {
            return '';
        }

        return substr($html, $start, $end - $start);
    }

    public function test_drawer_close_control_renders_inside_the_menu_and_is_revealed_when_open(): void
    {
        $response = $this->get(route('home'))->assertOk();

        // The close control must live inside the navigation (above the menu
        // links), otherwise it sits behind the open drawer and is unclickable.
        $nav = $this->extractNavHtml($response);

        static::assertStringContainsString('data-nav-close', $nav);
        static::assertStringContainsString(
            __('components.header.menu_close'),
            $nav,
        );

        $css = file_get_contents(resource_path('css/components/site-header.css'));

        // Hidden by default...
        static::assertMatchesRegularExpression(
            '/\.site-header__nav-top\s*\{\s*display:\s*none;/',
            $css,
        );

        // ...but the drawer override must out-specify that default. With equal
        // specificity the later `display: none` wins even when the drawer is
        // open, leaving the X hidden behind the menu.
        static::assertMatchesRegularExpression(
            '/\.site-header__nav\s+\.site-header__nav-top\s*\{\s*display:\s*flex;/',
            $css,
        );
    }

    public function test_the_header_actions_resolve_to_the_routes_they_promise(): void
    {
        // Every guard in the header resolves true, so no action falls back to a
        // link that goes nowhere.
        foreach ([
            'cart.index',
            'wishlist.add',
            'login',
            'register',
            'account.index',
        ] as $name) {
            static::assertTrue(
                Route::has($name),
                "The header's {$name} route is missing.",
            );
        }

        $html = $this->get(route('home'))->assertOk()->getContent();

        static::assertStringContainsString(
            'href="'.route('cart.index').'"',
            $html,
        );
        static::assertStringContainsString('href="'.route('login').'"', $html);
        static::assertStringNotContainsString('href="#"', $html);
    }

    public function test_parent_categories_render_disclosure_toggles_with_paired_panels(): void
    {
        $parent = Category::factory()->active()->create();
        $child = Category::factory()
            ->active()
            ->create(['parent_id' => $parent->id]);

        $this
            ->get(route('home'))
            ->assertOk()
            ->assertSee('class="site-header__nav-toggle"', false)
            ->assertSee('aria-controls="submenu-'.$parent->id.'"', false)
            ->assertSee('id="submenu-'.$parent->id.'"', false)
            ->assertSee($child->name);
    }

    public function test_submenu_is_single_column_and_hidden_by_default(): void
    {
        $parent = Category::factory()->active()->create();
        $child = Category::factory()
            ->active()
            ->create(['parent_id' => $parent->id]);

        $this
            ->get(route('home'))
            ->assertOk()
            ->assertSee(
                '<ul class="site-header__submenu" id="submenu-'.$parent->id.'" hidden>',
                false,
            )
            ->assertSee('class="site-header__submenu-link"', false)
            ->assertDontSee('site-header__dropdown', false)
            ->assertSee($child->name);
    }

    public function test_submenu_is_limited_to_two_levels_ignoring_grandchildren(): void
    {
        $parent = Category::factory()->active()->create();
        $child = Category::factory()
            ->active()
            ->create(['parent_id' => $parent->id]);
        $grandchild = Category::factory()
            ->active()
            ->create(['parent_id' => $child->id]);

        $response = $this->get(route('home'))->assertOk();

        // Grandchildren must not render inside the navigation submenu, even
        // though they may appear elsewhere on the page (e.g. the homepage
        // categories grid).
        $nav = $this->extractNavHtml($response);

        static::assertStringContainsString('class="site-header__submenu"', $nav);
        static::assertStringNotContainsString(
            'site-header__submenu-group',
            $nav,
        );
        static::assertStringNotContainsString($grandchild->name, $nav);
    }

    public function test_visiting_a_subcategory_selects_its_parent_and_itself(): void
    {
        $parent = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $child = Category::factory()
            ->active()
            ->create(['parent_id' => $parent->id]);

        $nav = $this->extractNavHtml(
            $this->get(route('category.show', $child->slug))->assertOk(),
        );

        static::assertMatchesRegularExpression(
            '/site-header__nav-link--active"\s+href="[^"]*'.$parent->slug.'"/',
            $nav,
        );
        static::assertMatchesRegularExpression(
            '/site-header__submenu-link--active"\s+href="[^"]*'.$child->slug.'"/',
            $nav,
        );
    }

    public function test_visiting_a_top_level_category_selects_only_that_link(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $sibling = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $child = Category::factory()
            ->active()
            ->create(['parent_id' => $sibling->id]);

        $nav = $this->extractNavHtml(
            $this->get(route('category.show', $category->slug))->assertOk(),
        );

        static::assertMatchesRegularExpression(
            '/site-header__nav-link--active"\s+href="[^"]*'.$category->slug.'"/',
            $nav,
        );
        static::assertSame(
            1,
            substr_count($nav, 'site-header__nav-link--active'),
        );
        static::assertStringNotContainsString(
            'site-header__submenu-link--active',
            $nav,
        );
    }

    public function test_deeply_nested_category_selects_its_top_level_ancestor(): void
    {
        $grandparent = Category::factory()
            ->active()
            ->create(['parent_id' => null]);
        $parent = Category::factory()
            ->active()
            ->create(['parent_id' => $grandparent->id]);
        $child = Category::factory()
            ->active()
            ->create(['parent_id' => $parent->id]);

        $nav = $this->extractNavHtml(
            $this->get(route('category.show', $child->slug))->assertOk(),
        );

        static::assertMatchesRegularExpression(
            '/site-header__nav-link--active"\s+href="[^"]*'.$grandparent->slug.'"/',
            $nav,
        );

        // The intermediate level is not part of the two-level navigation.
        static::assertStringNotContainsString(
            'site-header__submenu-link--active',
            $nav,
        );
    }

    private function extractNavHtml($response): string
    {
        $html = $response->getContent();

        preg_match(
            '/<nav class="site-header__nav.*?<\/nav>/s',
            $html,
            $matches,
        );

        return $matches[0] ?? '';
    }

    public function test_categories_without_children_have_no_toggle(): void
    {
        $leaf = Category::factory()
            ->active()
            ->create(['parent_id' => null]);

        $this
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('class="site-header__nav-toggle"', false)
            ->assertSee($leaf->name);
    }

    public function test_submenu_opening_is_state_driven_and_has_a_desktop_hover_bridge(): void
    {
        $css = file_get_contents(resource_path('css/components/site-header.css'));

        // Opened from the toggle's aria-expanded, so it works on every size.
        static::assertStringContainsString(
            '.site-header__nav-toggle[aria-expanded="true"] ~ .site-header__submenu',
            $css,
        );

        // The hover bridge closes the gap that used to make the menu vanish.
        static::assertStringContainsString(
            '.site-header__nav-item::after',
            $css,
        );
        static::assertStringContainsString(
            'block-size: var(--_submenu-offset)',
            $css,
        );

        // Desktop-only hover rules and the mobile drawer breakpoint exist
        // (the category nav collapses below 1160px / tablet sizes).
        static::assertStringContainsString('@media (min-width: 1161px)', $css);
        static::assertStringContainsString('@media (max-width: 1160px)', $css);
    }

    public function test_the_keyboard_can_walk_the_navigation_without_revealing_hidden_menus(): void
    {
        $css = file_get_contents(resource_path('css/components/site-header.css'));
        $js = file_get_contents(resource_path('js/components/header.js'));

        // A collapsed panel is shown by its toggle's aria-expanded and by
        // nothing else. Were focus alone enough to reveal it, a closed submenu
        // would hand its links to the Tab key while reading out as collapsed.
        static::assertStringNotContainsString(':focus-within', $css);

        // That one state is what the stylesheet listens to...
        static::assertStringContainsString(
            '.site-header__nav-toggle[aria-expanded="true"] ~ .site-header__submenu',
            $css,
        );

        // ...and the script is what answers the keys a keyboard user drives
        // the bar, the open panels and the drawer with.
        foreach (['ArrowDown', 'ArrowUp', 'ArrowLeft', 'ArrowRight', 'Escape'] as $key) {
            static::assertStringContainsString(
                $key,
                $js,
                "The header script ignores {$key}.",
            );
        }
    }

    public function test_top_bar_actions_render_icons_instead_of_emoji(): void
    {
        $this
            ->get(route('home'))
            ->assertOk()
            ->assertSee('site-header__action-icon', false)
            ->assertDontSee('🌐')
            ->assertDontSee('👤')
            ->assertDontSee('🛒');
    }

    public function test_top_bar_actions_are_grouped_and_consistently_styled(): void
    {
        $this
            ->get(route('home'))
            ->assertOk()
            ->assertSee(__('components.header.login'))
            ->assertSee(__('components.header.cart'))
            // Preferences group (language + currency).
            ->assertSee('site-header__group', false)
            // One account trigger opens the shared login/register modal.
            ->assertSee('data-auth-open', false)
            ->assertSee('aria-controls="auth-modal"', false)
            ->assertDontSee('site-header__group--account', false)
            // Cart is a standalone button.
            ->assertSee('site-header__cart', false)
            // Every action uses the same secondary button style.
            ->assertSee('btn btn--secondary', false)
            ->assertSee('site-header__action', false)
            // Currency chip is hidden until multi-currency is available.
            ->assertDontSee('site-header__currency', false);
    }

    public function test_account_trigger_opens_a_modal_with_both_auth_forms(): void
    {
        $this
            ->get(route('home'))
            ->assertOk()
            ->assertSee('id="auth-modal"', false)
            ->assertSee('class="auth-modal__panels"', false)
            // One panel per form, rendered side by side (stacked on mobile).
            ->assertSee('class="auth-panel"', false)
            ->assertSee(__('components.header.login'))
            ->assertSee(__('components.header.signup'))
            // Login fields.
            ->assertSee('id="auth-login-email"', false)
            ->assertSee('id="auth-login-password"', false)
            ->assertSee('name="remember"', false)
            // Register fields.
            ->assertSee('id="auth-register-name"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('method="POST"', false);
    }

    public function test_navigation_metrics_stay_compact_with_many_categories(): void
    {
        $css = file_get_contents(resource_path('css/components/site-header.css'));
        $utilities = file_get_contents(resource_path('css/utilities.css'));

        static::assertStringContainsString('.wrapper--wide', $utilities);
        static::assertStringContainsString('--_nav-gap: var(--space-300)', $css);
        static::assertStringContainsString('column-gap: var(--_nav-gap)', $css);
        static::assertStringContainsString('text-overflow: ellipsis', $css);
        static::assertStringContainsString('.site-header__nav-link-label', $css);

        Category::factory()
            ->active()
            ->create(['parent_id' => null]);

        $this
            ->get(route('home'))
            ->assertOk()
            ->assertSee('wrapper wrapper--wide', false)
            ->assertSee('site-header__nav-link-label', false);
    }

    public function test_small_screens_move_language_and_account_actions_into_the_drawer(): void
    {
        $this
            ->get(route('home'))
            ->assertOk()
            ->assertSee('site-header__drawer-actions', false)
            // Labels collapse to icon-only in the top bar on small screens.
            ->assertSee('site-header__action-label', false);

        $css = file_get_contents(resource_path('css/components/site-header.css'));

        static::assertStringContainsString(
            '.site-header__drawer-actions {',
            $css,
        );
        static::assertStringContainsString(
            '.site-header__actions .site-header__group {',
            $css,
        );
        static::assertStringContainsString(
            '.site-header__actions .site-header__action-label {',
            $css,
        );
    }
}
