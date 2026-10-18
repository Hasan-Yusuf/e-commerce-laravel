<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Models\ContactMessage;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin panel coverage for the ContactMessages resource.
 *
 * The resource is read-only (list + view) with a single "mark read" action, so
 * coverage is limited to listing, viewing, and that action.
 */
class ContactMessagesResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_lists_contact_messages(): void
    {
        $this->actingAsAdmin();

        $messages = ContactMessage::factory()->count(3)->create();

        Livewire::test(ListContactMessages::class)
            ->assertOk()
            ->assertCanSeeTableRecords($messages);
    }

    public function test_it_views_a_contact_message(): void
    {
        $this->actingAsAdmin();

        $message = ContactMessage::factory()->create();

        Livewire::test(ViewContactMessage::class, [
            'record' => $message->getRouteKey(),
        ])
            ->assertOk()
            ->assertSee($message->subject)
            ->assertSee($message->message);
    }

    public function test_it_marks_a_message_as_read(): void
    {
        $this->actingAsAdmin();

        $message = ContactMessage::factory()->create(['read_at' => null]);

        Livewire::test(ListContactMessages::class)
            ->callAction(TestAction::make('read_at')->table($message))
            ->assertNotified();

        static::assertNotNull($message->refresh()->read_at);
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    }
}
