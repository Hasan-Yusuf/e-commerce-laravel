<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Type;

/**
 * Keeps the values a type's products hold in step with its spec definitions.
 *
 * Those values live in the products' `specs_en` / `specs_ar` JSON columns, keyed
 * by the spec's localized name, so a spec that is renamed leaves its values
 * behind under a key nothing reads any more, and one that is added, removed or
 * re-typed leaves them missing or wrongly shaped. A rename can be carried over
 * — the values are simply moved onto the names the schema declares now — but
 * nothing else can be guessed from a stored value ("1 ms GtG" cannot become a
 * number on its own), which is what the "update the products" page in the panel
 * is for: it is where the admin goes over them and where each value is rebuilt
 * from the schema.
 */
class ProductSpecSyncService
{
    public function __construct(
        private SpecSchemaComparator $schema,
        private SpecValueRenamer $renamer,
    ) {}

    /**
     * The specs a type declares right now, by id — the shape both methods below
     * compare the schema against, taken before an edit and after it.
     *
     * @return array<int, array<string, mixed>>
     */
    public function snapshot(Type $type): array
    {
        return $this->schema->snapshot($type);
    }

    /**
     * Whether an edit to the schema left the products' stored values wrong or
     * missing: a spec that was added, removed or renamed, one whose value type
     * changed, and one whose options changed, all ask for a value the products
     * either do not hold or no longer answer. A cosmetic change — the unit, the
     * order, whether the spec is filterable — leaves what is stored alone.
     *
     * @param  array<int, array<string, mixed>>  $previous
     */
    public function affectsProducts(Type $type, array $previous): bool
    {
        return $this->schema->affectsProducts($type, $previous);
    }

    /**
     * Note that a type's products are out of step with its specs, so they have to
     * be gone through before they are trusted again. The flag is the panel's own,
     * not the admin's input, which is why it is filled rather than mass-assigned,
     * and setting it is a catalogue decision rather than something the type tells
     * itself to do (Type::needsSpecReview() only reads it back).
     */
    public function flagForReview(Type $type): void
    {
        if ($type->needsSpecReview()) {
            return;
        }

        $type->forceFill(['specs_need_review' => true])->save();
    }

    /**
     * Note that a type's products have been gone through against its current
     * specs.
     */
    public function markReviewed(Type $type): void
    {
        if (! $type->needsSpecReview()) {
            return;
        }

        $type->forceFill(['specs_need_review' => false])->save();
    }

    /**
     * Move the values of the specs that were renamed onto the names the schema
     * declares now, so a rename cannot leave a catalogue without its values.
     * Answers how many products were moved.
     *
     * @param  array<int, array<string, mixed>>  $previous
     */
    public function renameValues(Type $type, array $previous): int
    {
        return $this->renamer->rename($type, $previous);
    }
}
