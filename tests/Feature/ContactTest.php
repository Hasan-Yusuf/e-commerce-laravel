<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\FlashMessage;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_contact_page_renders_the_details_and_the_form(): void
    {
        $this
            ->get(route('contact.index'))
            ->assertOk()
            ->assertSee(__('contact.title'))
            ->assertSee(__('contact.form.submit'))
            // Where the store can be reached, and where the form writes to.
            ->assertSee('17000000')
            ->assertSee(route('contact.store'), false);
    }

    public function test_a_guest_can_send_a_message_without_an_account(): void
    {
        Mail::fake();

        $this
            ->post(route('contact.store'), $this->messagePayload())
            ->assertRedirect(route('contact.index'))
            ->assertSessionHas(FlashMessage::TITLE, __('contact.form.success'));

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Layla Hassan',
            'email' => 'layla@example.com',
            'subject' => 'A question about an order',
            'message' => 'Is order 1234 on its way?',
            // Nobody has read it yet.
            'read_at' => null,
        ]);

        Mail::assertSent(
            ContactMessageMail::class,
            static fn (ContactMessageMail $mail): bool => $mail->hasTo(
                config('mail.contact_address'),
            ),
        );
    }

    public function test_the_page_shows_that_the_message_arrived(): void
    {
        Mail::fake();

        $this->post(route('contact.store'), $this->messagePayload())
            ->assertRedirect(route('contact.index'));

        $this
            ->get(route('contact.index'))
            ->assertSee(__('contact.form.success_title'))
            ->assertSee(__('contact.form.success'));
    }

    public function test_a_message_needs_a_name_an_email_a_subject_and_a_message(): void
    {
        Mail::fake();

        $this->post(route('contact.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingSent();
    }

    public function test_a_message_refuses_an_email_that_is_not_an_address(): void
    {
        $this->post(
            route('contact.store'),
            $this->messagePayload([
                'email' => 'not an address',
            ]),
        )->assertSessionHasErrors('email');

        $this->assertDatabaseCount('contact_messages', 0);
    }

    /**
     * The fields the table holds in a column of their own, and a value that is
     * one character too long for it.
     *
     * @return array<string, array{string, string}>
     */
    public static function fieldsHeldInAColumnOfTheirOwn(): array
    {
        return [
            'name' => ['name', str_repeat('a', 256)],
            'subject' => ['subject', str_repeat('a', 256)],
            'email' => ['email', str_repeat('a', 244).'@example.com'],
        ];
    }

    #[DataProvider('fieldsHeldInAColumnOfTheirOwn')]
    public function test_a_field_longer_than_its_column_is_refused(
        string $field,
        string $value,
    ): void {
        $this->post(
            route('contact.store'),
            $this->messagePayload([
                $field => $value,
            ]),
        )->assertSessionHasErrors($field);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_a_message_longer_than_the_column_allows_is_refused(): void
    {
        $this->post(
            route('contact.store'),
            $this->messagePayload([
                'message' => str_repeat('a', 5001),
            ]),
        )->assertSessionHasErrors('message');

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_the_honeypot_refuses_a_message_from_something_answering_it(): void
    {
        Mail::fake();

        $this->post(
            route('contact.store'),
            $this->messagePayload([
                'website' => 'https://spam.example',
            ]),
        )->assertSessionHasErrors('website');

        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingSent();
    }

    public function test_the_store_keeps_a_message_without_notifying_anyone_when_it_has_no_address(): void
    {
        Mail::fake();
        config()->set('mail.contact_address', null);

        $this->post(route('contact.store'), $this->messagePayload())
            ->assertRedirect(route('contact.index'));

        $this->assertDatabaseCount('contact_messages', 1);
        Mail::assertNothingSent();
    }

    public function test_the_store_refuses_more_than_five_messages_from_one_client(): void
    {
        Mail::fake();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('contact.store'), $this->messagePayload())
                ->assertRedirect(route('contact.index'));
        }

        $this->post(route('contact.store'), $this->messagePayload())
            ->assertTooManyRequests();

        $this->assertDatabaseCount('contact_messages', 5);
    }

    public function test_a_refused_message_comes_back_with_what_was_written(): void
    {
        $this->post(
            route('contact.store'),
            $this->messagePayload([
                'subject' => '',
            ]),
        );

        $this
            ->get(route('contact.index'))
            ->assertSee('Layla Hassan')
            ->assertSee('layla@example.com')
            ->assertSee('Is order 1234 on its way?');
    }

    public function test_a_refused_field_is_marked_for_a_screen_reader(): void
    {
        $this->post(
            route('contact.store'),
            $this->messagePayload([
                'message' => '',
            ]),
        );

        $this
            ->get(route('contact.index'))
            // The message field points at the error it is described by, and
            // says that what it holds was refused.
            ->assertSee('aria-describedby="field-message-error"', false)
            ->assertSee('aria-invalid="true"', false);
    }

    public function test_the_contact_page_renders_in_arabic_and_mirrors_the_layout(): void
    {
        $this->post(route('lang'), ['language' => 'ar']);

        $this
            ->get(route('contact.index'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee(__('contact.title', locale: 'ar'))
            ->assertSee(__('contact.form.submit', locale: 'ar'))
            ->assertSee(__('contact.info.title', locale: 'ar'));
    }

    public function test_the_notification_carries_the_message_it_was_sent_about(): void
    {
        $message = ContactMessage::factory()->create([
            'name' => 'Layla Hassan',
            'email' => 'layla@example.com',
            'subject' => 'Where is my order?',
            'message' => 'It was meant to arrive on Sunday.',
        ]);

        $mail = new ContactMessageMail($message);

        $mail
            ->assertSeeInHtml('Where is my order?')
            ->assertSeeInHtml('Layla Hassan')
            ->assertSeeInHtml('It was meant to arrive on Sunday.')
            ->assertSeeInHtml('layla@example.com');
    }

    public function test_the_notification_invites_the_store_to_answer_the_shopper(): void
    {
        $message = ContactMessage::factory()->create([
            'email' => 'layla@example.com',
            'subject' => 'Where is my order?',
        ]);

        $envelope = (new ContactMessageMail($message))->envelope();

        static::assertTrue($envelope->hasSubject('Contact form: Where is my order?'));
        static::assertTrue($envelope->hasReplyTo('layla@example.com'));
    }

    /**
     * A message the contact form would send, with the fields a case changes.
     *
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function messagePayload(array $overrides = []): array
    {
        return array_merge(
            [
                'name' => 'Layla Hassan',
                'email' => 'layla@example.com',
                'subject' => 'A question about an order',
                'message' => 'Is order 1234 on its way?',
                // The honeypot, left empty as the form leaves it.
                'website' => '',
            ],
            $overrides,
        );
    }
}
