<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * The shared radio and checkbox controls, and the script that finishes the
 * wrapper off: the label is the click target for the whole line, and the
 * wrapper's own box — the padding inside its border, when a choice is drawn as
 * a card — changes the control exactly once.
 */
class ChoiceControlsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_choice_component_wraps_the_control_in_its_label(): void
    {
        Product::factory()
            ->active()
            ->create([
                'stock' => 5,
                'reserved_stock' => 0,
                'low_stock_threshold' => 0,
            ]);

        $html = $this->get(route('products.index'))->assertOk()->getContent();

        // The wrapper is the label and the control is inside it. That is what
        // makes the whole line the click target, and what lets the script reach
        // the control from a click on the wrapper itself.
        static::assertMatchesRegularExpression(
            '/<label[^>]*class="[^"]*filter-checkbox[^"]*"[^>]*>[\s\S]*?<input[^>]*type="checkbox"/',
            $html,
            'A choice control is not wrapped in the label that names it.',
        );
    }

    public function test_the_script_makes_the_change_the_label_would_have_made_twice(): void
    {
        $script = file_get_contents(
            resource_path('js/components/checkable-fields.js'),
        );

        // The wrapper's own box is the script's to handle, and it stops the
        // label's activation first: left to run, the two would cancel each
        // other out and a checkbox clicked inside its border would not move.
        static::assertStringContainsString('preventDefault()', $script);

        // The one change is then the control's own click, so whatever listens
        // to the control hears the same events a real click would fire.
        static::assertStringContainsString('.click()', $script);

        // It works on the shared choice wrappers.
        static::assertStringContainsString(
            '.radio-field, .checkbox-field',
            $script,
        );
    }
}
