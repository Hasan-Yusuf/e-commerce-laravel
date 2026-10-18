<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Loads the relations a view page's infolist reads, wherever the record it shows
 * comes from.
 *
 * Lazy loading is off outside production, so a page that reached for a relation
 * it had not loaded would fail rather than quietly query per row — and the
 * record has to bring them every time, not only when the page is first mounted:
 * Livewire carries a model between requests by its key alone, so every request
 * after the first gets it back bare and the page has to fill it in again.
 *
 * @property Model|null $record
 */
trait HasInfoRelations
{
    /**
     * The relations the page's infolist reads, named as its entries reach them.
     *
     * @return array<int, string>
     */
    abstract protected function infoRelations(): array;

    /**
     * Mounting reads the record, and what the page shows comes with it.
     */
    #[\Override]
    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->loadMissing($this->infoRelations());
    }

    /**
     * Every request after the first gets the record back without its relations,
     * so they are loaded again here.
     */
    #[\Override]
    public function hydrate(): void
    {
        parent::hydrate();

        $this->record?->loadMissing($this->infoRelations());
    }
}
