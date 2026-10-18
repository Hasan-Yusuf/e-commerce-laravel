<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FooterTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_footer_renders_the_contact_details(): void
    {
        $footer = $this->extractFooterHtml(
            $this->get(route('home'))->assertOk(),
        );

        static::assertStringContainsString('href="tel:+97317000000"', $footer);
        static::assertStringContainsString('17000000', $footer);
        static::assertStringContainsString(
            'href="https://www.instagram.com/nexus"',
            $footer,
        );
        static::assertStringContainsString(
            'href="https://www.tiktok.com/@nexus"',
            $footer,
        );
    }

    public function test_footer_links_the_contact_page(): void
    {
        $footer = $this->extractFooterHtml(
            $this->get(route('home'))->assertOk(),
        );

        static::assertStringContainsString(
            'href="'.route('contact.index').'"',
            $footer,
        );
        static::assertStringContainsString(
            __('components.footer.contact'),
            $footer,
        );
    }

    public function test_social_links_open_in_a_new_tab_safely(): void
    {
        $footer = $this->extractFooterHtml(
            $this->get(route('home'))->assertOk(),
        );

        // Both social links, and neither the phone link.
        static::assertSame(2, substr_count($footer, 'target="_blank"'));
        static::assertSame(
            2,
            substr_count($footer, 'rel="noopener noreferrer"'),
        );
    }

    public function test_footer_appears_on_every_page_like_the_header(): void
    {
        $category = Category::factory()
            ->active()
            ->create(['parent_id' => null]);

        $urls = [
            route('home'),
            route('category.index'),
            route('category.show', $category->slug),
            route('cart.index'),
        ];

        foreach ($urls as $url) {
            static::assertStringContainsString(
                '<footer class="site-footer">',
                $this->get($url)->assertOk()->getContent(),
                "The footer is missing on {$url}.",
            );
        }
    }

    public function test_footer_shares_the_wide_rail_and_wraps_intrinsically(): void
    {
        $css = file_get_contents(
            resource_path('css/components/site-footer.css'),
        );

        $this
            ->get(route('home'))
            ->assertOk()
            ->assertSee(
                '<div class="site-footer__inner wrapper wrapper--wide">',
                false,
            );

        // Rows are produced by wrapping, not by a viewport breakpoint.
        static::assertMatchesRegularExpression(
            '/\.site-footer__inner \{[^}]*flex-wrap: wrap;/s',
            $css,
        );
    }

    private function extractFooterHtml($response): string
    {
        preg_match(
            '/<footer class="site-footer">.*?<\/footer>/s',
            $response->getContent(),
            $matches,
        );

        return $matches[0] ?? '';
    }
}
