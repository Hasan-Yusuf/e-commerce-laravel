<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\RelationManagers\OrdersRelationManager;
use App\Models\Order;
use App\Models\Phone;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin panel coverage for the Users resource.
 *
 * Assertions avoid counting table records because the resource's base query
 * scopes the list and the authenticated admin is itself a User.
 */
class UsersResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_lists_customers(): void
    {
        $this->actingAsAdmin();

        $user = User::factory()->create([
            'status' => UserStatus::ACTIVE,
        ]);

        Livewire::test(ListUsers::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$user]);
    }

    public function test_it_does_not_list_admin_users(): void
    {
        $this->actingAsAdmin();

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        Livewire::test(ListUsers::class)
            ->assertOk()
            ->assertCanNotSeeTableRecords([$admin]);
    }

    public function test_it_shows_a_user(): void
    {
        $this->actingAsAdmin();

        $user = User::factory()
            ->hasAddresses()
            ->create([
                'status' => UserStatus::ACTIVE,
                'phone_id' => Phone::factory(),
            ]);

        Livewire::test(ViewUser::class, [
            'record' => $user->getRouteKey(),
        ])
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email);
    }

    public function test_it_filters_customers_by_status(): void
    {
        $this->actingAsAdmin();

        $active = User::factory()->create(['status' => UserStatus::ACTIVE]);
        $inactive = User::factory()->create([
            'status' => UserStatus::IN_ACTIVE,
        ]);

        Livewire::test(ListUsers::class)
            ->filterTable('status', UserStatus::ACTIVE->value)
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive]);
    }

    public function test_it_shows_a_customers_orders(): void
    {
        $this->actingAsAdmin();

        $user = User::factory()->create(['status' => UserStatus::ACTIVE]);
        $order = Order::factory()->create(['user_id' => $user->id]);

        Livewire::test(OrdersRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => ViewUser::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords([$order]);
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    }
}
