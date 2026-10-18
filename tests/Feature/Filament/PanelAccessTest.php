<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_an_administrator_reaches_the_panel(): void
    {
        $this
            ->actingAs(
                User::factory()->create(['role' => UserRole::ADMIN]),
            )
            ->get('/admin')
            ->assertOk();
    }

    public function test_a_shopper_is_told_there_is_no_such_page(): void
    {
        $this
            ->actingAs(
                User::factory()->create(['role' => UserRole::CUSTOMER]),
            )
            ->get('/admin')
            ->assertNotFound();
    }

    public function test_a_shopper_is_told_the_same_about_a_panel_page(): void
    {
        $this
            ->actingAs(
                User::factory()->create(['role' => UserRole::CUSTOMER]),
            )
            ->get('/admin/orders')
            ->assertNotFound();
    }

    public function test_a_guest_is_sent_to_the_panel_login(): void
    {
        $this->get('/admin')->assertRedirect(route('filament.admin.auth.login'));
    }
}
