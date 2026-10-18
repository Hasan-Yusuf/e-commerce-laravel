<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\DiscountType;
use App\Enums\UserRole;
use App\Filament\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Resources\Coupons\Pages\EditCoupon;
use App\Filament\Resources\Coupons\Pages\ListCoupons;
use App\Filament\Resources\Coupons\Pages\ViewCoupon;
use App\Models\Coupon;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin panel coverage for the Coupons resource.
 *
 * The coupons table exposes view and edit record actions (plus a bulk delete),
 * so deletion is covered through the delete header action on the edit page.
 */
class CouponsResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_lists_coupons(): void
    {
        $this->actingAsAdmin();

        $coupons = Coupon::factory()->count(3)->create();

        Livewire::test(ListCoupons::class)
            ->assertOk()
            ->assertCanSeeTableRecords($coupons);
    }

    public function test_it_requires_the_mandatory_fields_to_create_a_coupon(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateCoupon::class)
            ->fillForm([
                'code' => null,
                'discount_type' => null,
                'discount_value' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'code' => 'required',
                'discount_type' => 'required',
                'discount_value' => 'required',
            ])
            ->assertNotNotified();

        $this->assertDatabaseCount('coupons', 0);
    }

    public function test_it_creates_a_coupon(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateCoupon::class)
            ->fillForm([
                'code' => 'SAVE10',
                'discount_type' => DiscountType::PERCENTAGE->value,
                'discount_value' => 10,
                'min_order_amount' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('coupons', [
            'code' => 'save10',
            'discount_type' => DiscountType::PERCENTAGE->value,
        ]);

        $coupon = Coupon::query()->firstOrFail();

        static::assertNotNull($coupon->starts_at);
        static::assertNotNull($coupon->expires_at);
    }

    public function test_a_percentage_coupon_may_not_exceed_a_hundred(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateCoupon::class)
            ->fillForm([
                'code' => 'EVERYTHING',
                'discount_type' => DiscountType::PERCENTAGE->value,
                'discount_value' => 150,
            ])
            ->call('create')
            ->assertHasFormErrors(['discount_value']);

        $this->assertDatabaseCount('coupons', 0);
    }

    public function test_a_fixed_coupon_is_not_capped_at_a_hundred(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateCoupon::class)
            ->fillForm([
                'code' => 'BULK',
                'discount_type' => DiscountType::FIXED_AMOUNT->value,
                'discount_value' => 150,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('coupons', ['code' => 'bulk']);
    }

    public function test_it_stores_a_fixed_value_and_the_amounts_in_fils(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateCoupon::class)
            ->fillForm([
                'code' => 'TAKE5',
                'discount_type' => DiscountType::FIXED_AMOUNT->value,
                'discount_value' => 5,
                'maximum_discount' => 3,
                'min_order_amount' => 75,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $coupon = Coupon::query()->firstOrFail();

        // The amounts are entered in BHD and kept in fils (1000 fils = 1 BHD).
        static::assertSame(5_000, (int) $coupon->getRawOriginal('discount_value_in_fils'));
        static::assertSame(3_000, (int) $coupon->getRawOriginal('maximum_discount_in_fils'));
        static::assertSame(75_000, (int) $coupon->getRawOriginal('min_order_amount'));

        // ...and read back in BHD.
        $coupon->refresh();

        static::assertSame(5.0, $coupon->discount_value);
        static::assertSame(3.0, $coupon->maximum_discount);
        static::assertSame(75.0, $coupon->min_order_amount);
    }

    public function test_a_percentage_value_is_stored_as_the_percentage_it_is(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateCoupon::class)
            ->fillForm([
                'code' => 'SAVE12',
                'discount_type' => DiscountType::PERCENTAGE->value,
                'discount_value' => 12.5,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $coupon = Coupon::query()->firstOrFail();

        // A share is not money: it is kept in its own percentage column.
        static::assertSame(12.5, (float) $coupon->getRawOriginal('discount_percentage'));
        static::assertNull($coupon->getRawOriginal('discount_value_in_fils'));
        static::assertSame(12.5, $coupon->refresh()->discount_value);
    }

    public function test_it_edits_a_coupon(): void
    {
        $this->actingAsAdmin();

        $coupon = Coupon::factory()->create();

        Livewire::test(EditCoupon::class, [
            'record' => $coupon->getRouteKey(),
        ])
            ->fillForm([
                'description' => 'Only for the summer sale.',
                'discount_value' => 25,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'description' => 'Only for the summer sale.',
        ]);

        static::assertSame(25.0, (float) $coupon->refresh()->discount_value);
    }

    public function test_it_shows_a_coupons_usage_against_its_limit(): void
    {
        $this->actingAsAdmin();

        $coupon = Coupon::factory()->create([
            'usage_limit' => 10,
            'used_count' => 3,
        ]);

        Livewire::test(ListCoupons::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$coupon])
            ->assertSee((string) $coupon->used_count)
            ->assertSee((string) $coupon->usage_limit);
    }

    public function test_coupons_can_be_sorted_by_their_active_state(): void
    {
        $this->actingAsAdmin();

        Coupon::factory()->create();

        Livewire::test(ListCoupons::class)
            ->sortTable('is_active')
            ->assertOk();
    }

    public function test_coupons_can_be_filtered_by_their_type(): void
    {
        $this->actingAsAdmin();

        $percentage = Coupon::factory()->percentage()->create();
        $fixedAmount = Coupon::factory()->fixedAmount()->create();

        Livewire::test(ListCoupons::class)
            ->filterTable('discount_type', DiscountType::PERCENTAGE->value)
            ->assertOk()
            ->assertCanSeeTableRecords([$percentage])
            ->assertCanNotSeeTableRecords([$fixedAmount])
            ->assertCountTableRecords(1);
    }

    public function test_coupons_can_be_filtered_by_their_active_state(): void
    {
        $this->actingAsAdmin();

        $active = Coupon::factory()->create([
            'activated_at' => now()->subDay(),
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $inactive = Coupon::factory()->inactive()->create();

        Livewire::test(ListCoupons::class)
            ->filterTable('is_active', '1')
            ->assertOk()
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive])
            ->assertCountTableRecords(1);

        Livewire::test(ListCoupons::class)
            ->filterTable('is_active', '0')
            ->assertOk()
            ->assertCanSeeTableRecords([$inactive])
            ->assertCanNotSeeTableRecords([$active])
            ->assertCountTableRecords(1);
    }

    public function test_coupons_can_be_filtered_to_those_active_now(): void
    {
        $this->actingAsAdmin();

        $active = Coupon::factory()->create([
            'activated_at' => now()->subDay(),
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $inactive = Coupon::factory()->inactive()->create();

        Livewire::test(ListCoupons::class)
            ->filterTable('active_now')
            ->assertOk()
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive])
            ->assertCountTableRecords(1);
    }

    public function test_it_deletes_a_coupon(): void
    {
        $this->actingAsAdmin();

        $coupon = Coupon::factory()->create();

        Livewire::test(EditCoupon::class, [
            'record' => $coupon->getRouteKey(),
        ])
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertSoftDeleted('coupons', [
            'id' => $coupon->id,
        ]);
    }

    public function test_it_renders_the_view_page(): void
    {
        $this->actingAsAdmin();

        $coupon = Coupon::factory()->create();

        Livewire::test(ViewCoupon::class, [
            'record' => $coupon->getRouteKey(),
        ])->assertOk();
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    }
}
