<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Actions\SwitchColumnAction;
use App\Filament\Actions\SwitchDirection;
use App\Models\Product;
use App\Models\TypeSpec;
use Filament\Support\Icons\Heroicon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SwitchColumnActionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_boolean_columns_switch_between_true_and_false(): void
    {
        $spec = TypeSpec::factory()->create(['is_filterable' => true]);

        $action = SwitchColumnAction::make('is_filterable')->record($spec);

        static::assertTrue($action->isOn($spec));

        $action->call();

        static::assertFalse($spec->refresh()->is_filterable);

        $action->call();

        static::assertTrue($spec->refresh()->is_filterable);
    }

    public function test_date_columns_switch_between_the_current_time_and_null(): void
    {
        $product = Product::factory()->create(['activated_at' => null]);

        $action = SwitchColumnAction::make('activated_at')->record($product);

        static::assertFalse($action->isOn($product));

        $action->call();

        $activatedAt = $product->refresh()->activated_at;

        static::assertNotNull($activatedAt);
        static::assertTrue($activatedAt->greaterThan(now()->subMinute()));
        static::assertTrue($activatedAt->lessThanOrEqualTo(now()));

        $action->call();

        static::assertNull($product->refresh()->activated_at);
    }

    public function test_date_columns_that_are_scheduled_for_the_future_are_off(): void
    {
        $product = Product::factory()->create(['activated_at' => now()->addDay()]);

        $action = SwitchColumnAction::make('activated_at')->record($product);

        static::assertFalse($action->isOn($product));
        static::assertTrue($action->willSwitchOn($product));

        $action->call();

        static::assertTrue($product->refresh()->isActive());
    }

    public function test_the_column_type_is_inferred_from_the_cast_and_can_be_overridden(): void
    {
        $product = Product::factory()->create(['activated_at' => now()]);

        $dateAction = SwitchColumnAction::make('activated_at')->record($product);
        $booleanAction = SwitchColumnAction::make('activated_at')->boolean()->record($product);

        static::assertTrue($dateAction->isOn($product));
        static::assertInstanceOf(Carbon::class, $dateAction->getOnValue($product));
        static::assertNull($dateAction->getOffValue($product));

        static::assertTrue($booleanAction->isOn($product));
        static::assertTrue($booleanAction->getOnValue($product));
        static::assertFalse($booleanAction->getOffValue($product));
    }

    public function test_the_switch_values_can_be_customised(): void
    {
        $product = Product::factory()->create(['activated_at' => null]);

        $activatedAt = now()->subYear();

        SwitchColumnAction::make('activated_at')
            ->onValue($activatedAt)
            ->record($product)
            ->call();

        static::assertSame(
            $activatedAt->toDateTimeString(),
            $product->refresh()->activated_at->toDateTimeString(),
        );
    }

    public function test_the_label_icon_and_color_describe_the_state_that_will_be_applied(): void
    {
        $spec = TypeSpec::factory()->create(['is_filterable' => false]);

        $action = SwitchColumnAction::make('is_filterable')
            ->onLabel('Turn on')
            ->offLabel('Turn off')
            ->onIcon(Heroicon::CheckCircle)
            ->offIcon(Heroicon::XCircle)
            ->onColor('success')
            ->offColor('danger')
            ->record($spec);

        static::assertSame(SwitchDirection::On, $action->directionFor($spec));
        static::assertSame('Turn on', $action->getSwitchLabel(SwitchDirection::On));
        static::assertSame(Heroicon::CheckCircle, $action->getSwitchIcon(SwitchDirection::On));
        static::assertSame('success', $action->getSwitchColor(SwitchDirection::On));

        $action->call();

        static::assertSame(SwitchDirection::Off, $action->directionFor($spec));
        static::assertSame('Turn off', $action->getSwitchLabel(SwitchDirection::Off));
        static::assertSame(Heroicon::XCircle, $action->getSwitchIcon(SwitchDirection::Off));
        static::assertSame('danger', $action->getSwitchColor(SwitchDirection::Off));
    }

    public function test_the_labels_default_to_the_switch_direction(): void
    {
        $spec = TypeSpec::factory()->create(['is_filterable' => false]);

        $action = SwitchColumnAction::make('is_filterable')->record($spec);

        static::assertSame('Switch On', $action->getSwitchLabel(SwitchDirection::On));
        static::assertSame('Switch Off', $action->getSwitchLabel(SwitchDirection::Off));
    }
}
