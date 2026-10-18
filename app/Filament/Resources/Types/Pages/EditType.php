<?php

declare(strict_types=1);

namespace App\Filament\Resources\Types\Pages;

use App\Enums\SemanticColor;
use App\Filament\Resources\Types\TypeResource;
use App\Models\Type;
use App\Services\Catalog\ProductSpecSyncService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Locked;

/**
 * @extends EditRecord<Type>
 */
class EditType extends EditRecord
{
    protected static string $resource = TypeResource::class;

    /**
     * The specs as they stood before this edit, keyed by id, so the save can
     * tell what changed under the values the type's products hold.
     *
     * Public and locked, because the save is a request of its own: a private
     * property would be back at its default by the time the specs are compared.
     *
     * @var array<int, array<string, mixed>>
     */
    #[Locked]
    public array $specsBefore = [];

    /**
     * Whether the save left products for the admin to go through, so the
     * redirect below can send them there.
     */
    private bool $productsNeedReview = false;

    #[\Override]
    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->specsBefore = app(ProductSpecSyncService::class)->snapshot($this->getRecord());
    }

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('updateProducts')
                ->label(TypeResource::trans('actions.update_products'))
                ->icon(Heroicon::OutlinedWrenchScrewdriver)
                ->color(SemanticColor::Warning->value)
                ->tooltip(TypeResource::trans('actions.update_products_tooltip'))
                ->visible(fn (): bool => $this->getRecord()->needsSpecReview())
                ->url(fn (): string => TypeResource::getUrl('products', [
                    'record' => $this->getRecord(),
                ])),

            DeleteAction::make(),
        ];
    }

    /**
     * Carry the renamed specs' values over to the names the schema declares now,
     * and if the edit left the products answering for something they do not
     * hold, raise the flag the "update the products" page clears and send the
     * admin there rather than back to the list.
     */
    protected function afterSave(): void
    {
        $type = $this->getRecord();
        $type->load('specDefinitions');

        $sync = app(ProductSpecSyncService::class);

        $sync->renameValues($type, $this->specsBefore);

        if (! $sync->affectsProducts($type, $this->specsBefore)) {
            return;
        }

        if (! $type->products()->exists()) {
            return;
        }

        $sync->flagForReview($type);

        $this->productsNeedReview = true;
    }

    #[\Override]
    protected function getRedirectUrl(): ?string
    {
        return
            $this->productsNeedReview
                ? TypeResource::getUrl('products', ['record' => $this->getRecord()])
                : parent::getRedirectUrl();
    }

    #[\Override]
    protected function getSavedNotification(): ?Notification
    {
        if (! $this->productsNeedReview) {
            return parent::getSavedNotification();
        }

        return Notification::make()
            ->warning()
            ->title(TypeResource::trans('notifications.products_need_review.title'))
            ->body(TypeResource::trans('notifications.products_need_review.body'));
    }
}
