<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\FlashMessage;
use App\Enums\ToastType;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FlashMessageTest extends TestCase
{
    public function test_it_writes_the_message_under_the_keys_it_reads_it_back_from(): void
    {
        $message = FlashMessage::success()
            ->withTitle('Saved')
            ->withBody('It was stored.');

        $this->withSession($message->toSession());

        static::assertSame(
            $message->toSession(),
            FlashMessage::fromSession()->toSession(),
        );
    }

    public function test_it_leaves_no_key_for_a_body_it_does_not_have(): void
    {
        static::assertSame(
            [
                FlashMessage::TITLE => 'Saved',
                FlashMessage::TYPE => ToastType::SUCCESS->value,
            ],
            FlashMessage::success()
                ->withTitle('Saved')
                ->toSession(),
        );
    }

    public function test_it_hands_its_parts_to_a_caller_that_speaks_them(): void
    {
        $withBody = FlashMessage::warning()
            ->withTitle('Only 3 left')
            ->withBody('The quantity was adjusted.');

        $withoutBody = FlashMessage::success()->withTitle('Saved');

        static::assertSame(
            [
                'type' => ToastType::WARNING->value,
                'title' => 'Only 3 left',
                'body' => 'The quantity was adjusted.',
            ],
            $withBody->toArray(),
        );

        // A part the message has none of is null here rather than missing: the
        // caller is a spread, and a spread has to name every parameter it fills.
        static::assertSame(
            [
                'type' => ToastType::SUCCESS->value,
                'title' => 'Saved',
                'body' => null,
            ],
            $withoutBody->toArray(),
        );
    }

    public function test_it_reads_nothing_back_from_a_page_that_flashed_nothing(): void
    {
        static::assertNull(FlashMessage::fromSession());
    }

    public function test_it_reads_nothing_back_from_a_blank_title(): void
    {
        $this->withSession([FlashMessage::TITLE => '']);

        static::assertNull(FlashMessage::fromSession());
    }

    public function test_it_reads_a_message_that_named_no_type_as_a_success(): void
    {
        $this->withSession([FlashMessage::TITLE => 'Saved']);

        static::assertSame(ToastType::SUCCESS, FlashMessage::fromSession()?->type);
    }

    public function test_it_reads_a_type_it_does_not_know_as_a_success(): void
    {
        $this->withSession([
            FlashMessage::TITLE => 'Saved',
            FlashMessage::TYPE => 'triumph',
        ]);

        static::assertSame(ToastType::SUCCESS, FlashMessage::fromSession()?->type);
    }

    public function test_it_ignores_a_body_that_is_not_a_line_of_words(): void
    {
        $this->withSession([
            FlashMessage::TITLE => 'Saved',
            FlashMessage::BODY => ['not', 'a', 'line'],
        ]);

        static::assertNull(FlashMessage::fromSession()?->body);
    }

    public function test_it_keeps_only_the_words_of_what_was_said(): void
    {
        $message = FlashMessage::success()
            ->withTitle("  Saved  \n")
            ->withBody(" It was\nstored.  ");

        static::assertSame('Saved', $message->title);
        static::assertSame('It was stored.', $message->body);
    }

    public function test_it_replaces_only_the_part_it_was_asked_to(): void
    {
        $message = FlashMessage::success()
            ->withTitle('Saved')
            ->withBody('It was stored.');

        $warning = $message->withType(ToastType::WARNING);
        $retitled = $message->withTitle('  Kept  ');

        static::assertSame(ToastType::WARNING, $warning->type);
        static::assertSame('Saved', $warning->title);
        static::assertSame('It was stored.', $warning->body);

        static::assertSame('Kept', $retitled->title);
        static::assertSame(ToastType::SUCCESS, $retitled->type);

        // The message both copies were taken from is left as it was.
        static::assertSame(
            [
                FlashMessage::TITLE => 'Saved',
                FlashMessage::TYPE => ToastType::SUCCESS->value,
                FlashMessage::BODY => 'It was stored.',
            ],
            $message->toSession(),
        );
    }

    /**
     * The bodies a message can be handed that say nothing.
     *
     * @return array<string, array{?string}>
     */
    public static function bodiesThatSayNothing(): array
    {
        return [
            'none' => [null],
            'spaces' => ['   '],
        ];
    }

    #[DataProvider('bodiesThatSayNothing')]
    public function test_a_body_of_nothing_is_left_out(?string $body): void
    {
        $message = FlashMessage::success('Saved', 'It was stored.')
            ->withBody($body);

        static::assertNull($message->body);
        static::assertArrayNotHasKey(FlashMessage::BODY, $message->toSession());
    }
}
