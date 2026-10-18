<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ToastType;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ToastTypeTest extends TestCase
{
    /**
     * Every type the stack knows, keyed by the word it is stored and dispatched
     * as.
     *
     * @return array<string, array{ToastType}>
     */
    public static function types(): array
    {
        return collect(ToastType::cases())
            ->mapWithKeys(static fn (ToastType $type): array => [
                $type->value => [$type],
            ])
            ->all();
    }

    #[DataProvider('types')]
    public function test_it_reads_the_type_a_label_names(ToastType $type): void
    {
        static::assertSame($type, ToastType::fromValue($type->value));
    }

    public function test_it_reads_a_type_that_arrives_as_itself(): void
    {
        // The enum and its word are two spellings of the same thing: whatever
        // arrives — a label from a flash, or the type itself from inside the
        // application — reads as the type it names.
        static::assertSame(
            ToastType::WARNING,
            ToastType::fromValue(ToastType::WARNING),
        );
    }

    public function test_it_falls_back_for_a_label_it_does_not_know(): void
    {
        static::assertSame(ToastType::INFO, ToastType::fromValue('successful'));
    }

    public function test_it_falls_back_for_a_label_that_is_not_a_word(): void
    {
        // A session is not the application's own memory: whatever it holds may
        // have been put there by anything.
        static::assertSame(ToastType::INFO, ToastType::fromValue(['success']));
        static::assertSame(ToastType::INFO, ToastType::fromValue(null));
    }

    public function test_it_takes_the_default_the_caller_gives(): void
    {
        // What a flash that named no type reads as: a page says this after it did
        // what the shopper asked, so it is a success rather than information.
        static::assertSame(
            ToastType::SUCCESS,
            ToastType::fromValue(null, ToastType::SUCCESS),
        );
    }

    #[DataProvider('types')]
    public function test_it_names_every_type_in_both_locales(ToastType $type): void
    {
        foreach (['en', 'ar'] as $locale) {
            $this->app->setLocale($locale);

            // A name with no line behind it reads back as the key itself, so this
            // fails loudly rather than passing on a placeholder.
            static::assertNotSame(
                "enums.toast_type.{$type->value}",
                $type->getLabel(),
                "The {$locale} label for \"{$type->value}\" is missing.",
            );
        }
    }
}
