<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\DTOs\FlashMessage;
use App\Enums\ToastType;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ToastsTest extends TestCase
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
    public function test_it_shows_a_toast_of_every_type(ToastType $type): void
    {
        Livewire::test('toasts')
            ->dispatch('notify', type: $type->value, title: 'A message')
            ->assertSee('A message')
            // The toast wears its own type's class, and the mark that goes with
            // it: an icon the stack cannot resolve would fail the render.
            ->assertSee('toast--'.$type->value, false)
            ->assertSee('toast__icon', false);
    }

    public function test_a_label_the_stack_does_not_know_is_shown_as_information(): void
    {
        Livewire::test('toasts')
            ->dispatch('notify', type: 'tomato', title: 'A message')
            ->assertSee('A message')
            ->assertSee('toast--info', false);
    }

    public function test_it_shows_a_flashed_status_as_a_toast(): void
    {
        $this->withSession(
            FlashMessage::success()
                ->withTitle('Saved successfully')
                ->withBody('Your changes were stored.')
                ->toSession(),
        );

        Livewire::test('toasts')
            ->assertSee('Saved successfully')
            ->assertSee('Your changes were stored.')
            ->assertSee('toast--success', false)
            ->assertSee('toast__progress', false);
    }

    public function test_a_flash_that_named_no_type_is_shown_as_a_success(): void
    {
        // What a page says after it did what the shopper asked: the type it
        // leaves unsaid is a success rather than information.
        $this->withSession([FlashMessage::TITLE => 'Saved successfully']);

        Livewire::test('toasts')
            ->assertSee('Saved successfully')
            ->assertSee('toast--success', false);
    }

    public function test_it_pushes_a_toast_from_the_notify_event(): void
    {
        Livewire::test('toasts')
            ->dispatch(
                'notify',
                type: 'warning',
                title: 'Quantity adjusted',
                body: 'Only 3 left in stock.',
            )
            ->assertSee('Quantity adjusted')
            ->assertSee('Only 3 left in stock.')
            ->assertSee('toast--warning', false);
    }

    public function test_it_dismisses_a_toast(): void
    {
        $component = Livewire::test('toasts')
            ->dispatch('notify', type: 'success', title: 'Done');

        $toasts = $component->get('toasts');
        $id = $toasts[0]['id'];

        $component
            ->call('dismiss', $id)
            ->assertSet('toasts', [])
            ->assertDontSee('Done');
    }
}
