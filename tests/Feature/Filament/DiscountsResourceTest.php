<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\DiscountType;
use App\Enums\UserRole;
use App\Filament\Resources\Discounts\Pages\CreateDiscount;
use App\Filament\Resources\Discounts\Pages\EditDiscount;
use App\Filament\Resources\Discounts\Pages\ListDiscounts;
use App\Filament\Resources\Discounts\Pages\ViewDiscount;
use App\Models\Discount;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin panel coverage for the Discounts resource.
 *
 * The discounts table only exposes view and edit record actions (plus a bulk
 * delete), so deletion is covered through the delete header action on the edit
 * page.
 */
class DiscountsResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_lists_discounts(): void
    {
        $this->actingAsAdmin();

        $discounts = Discount::factory()->count(3)->create();

        Livewire::test(ListDiscounts::class)
            ->assertOk()
            ->assertCanSeeTableRecords($discounts);
    }

    public function test_it_requires_the_mandatory_fields_to_create_a_discount(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateDiscount::class)
            ->fillForm([
                'name' => null,
                'type' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'type' => 'required',
            ])
            ->assertNotNotified();

        $this->assertDatabaseCount('discounts', 0);
    }

    public function test_it_creates_a_discount(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateDiscount::class)
            ->fillForm([
                'name' => 'Summer Sale',
                'type' => DiscountType::FIXED_AMOUNT->value,
                'value' => 5,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('discounts', [
            'name' => 'Summer Sale',
            'type' => DiscountType::FIXED_AMOUNT->value,
        ]);

        $discount = Discount::query()->firstOrFail();

        static::assertNotNull($discount->starts_at);
        static::assertNotNull($discount->ends_at);
    }

    public function test_a_percentage_discount_may_not_exceed_a_hundred(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateDiscount::class)
            ->fillForm([
                'name' => 'Everything Off',
                'type' => DiscountType::PERCENTAGE->value,
                'value' => 150,
            ])
            ->call('create')
            ->assertHasFormErrors(['value']);

        $this->assertDatabaseCount('discounts', 0);
    }

    public function test_a_fixed_amount_discount_is_not_capped_at_a_hundred(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateDiscount::class)
            ->fillForm([
                'name' => 'Bulk Deal',
                'type' => DiscountType::FIXED_AMOUNT->value,
                'value' => 150,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('discounts', ['name' => 'Bulk Deal']);
    }

    public function test_it_edits_a_discount(): void
    {
        $this->actingAsAdmin();

        $discount = Discount::factory()->create();

        Livewire::test(EditDiscount::class, [
            'record' => $discount->getRouteKey(),
        ])
            ->fillForm([
                'name' => 'Winter Sale',
                'value' => 25,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('discounts', [
            'id' => $discount->id,
            'name' => 'Winter Sale',
        ]);

        static::assertSame(25.0, (float) $discount->refresh()->value);
    }

    public function test_discounts_can_be_sorted_by_their_active_state(): void
    {
        $this->actingAsAdmin();

        Discount::factory()->create();

        Livewire::test(ListDiscounts::class)
            ->sortTable('is_active')
            ->assertOk();
    }

    public function test_discounts_can_be_filtered_by_their_type(): void
    {
        $this->actingAsAdmin();

        $percentage = Discount::factory()->percentage()->create();
        $fixedAmount = Discount::factory()->fixedAmount()->create();

        Livewire::test(ListDiscounts::class)
            ->filterTable('type', DiscountType::PERCENTAGE->value)
            ->assertOk()
            ->assertCanSeeTableRecords([$percentage])
            ->assertCanNotSeeTableRecords([$fixedAmount])
            ->assertCountTableRecords(1);
    }

    public function test_discounts_can_be_filtered_by_their_active_state(): void
    {
        $this->actingAsAdmin();

        $active = Discount::factory()->create([
            'activated_at' => now()->subDay(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $inactive = Discount::factory()->inactive()->create();

        Livewire::test(ListDiscounts::class)
            ->filterTable('is_active', '1')
            ->assertOk()
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive])
            ->assertCountTableRecords(1);

        Livewire::test(ListDiscounts::class)
            ->filterTable('is_active', '0')
            ->assertOk()
            ->assertCanSeeTableRecords([$inactive])
            ->assertCanNotSeeTableRecords([$active])
            ->assertCountTableRecords(1);
    }

    public function test_discounts_can_be_filtered_to_those_active_now(): void
    {
        $this->actingAsAdmin();

        $active = Discount::factory()->create([
            'activated_at' => now()->subDay(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $inactive = Discount::factory()->inactive()->create();

        Livewire::test(ListDiscounts::class)
            ->filterTable('active_now')
            ->assertOk()
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive])
            ->assertCountTableRecords(1);
    }

    public function test_it_deletes_a_discount(): void
    {
        $this->actingAsAdmin();

        $discount = Discount::factory()->create();

        Livewire::test(EditDiscount::class, [
            'record' => $discount->getRouteKey(),
        ])
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertDatabaseMissing('discounts', [
            'id' => $discount->id,
        ]);
    }

    public function test_it_renders_the_view_page(): void
    {
        $this->actingAsAdmin();

        $discount = Discount::factory()->create();

        Livewire::test(ViewDiscount::class, [
            'record' => $discount->getRouteKey(),
        ])->assertOk();
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    }
}
