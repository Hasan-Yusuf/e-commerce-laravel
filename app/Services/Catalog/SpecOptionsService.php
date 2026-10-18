<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\TypeSpec;

/**
 * Answers a spec's declared options in the other locale.
 *
 * A spec's `options` column holds one entry per option, and each entry carries
 * that option's label in every locale the application declares, in one order —
 * the locales' lists line up by position. That is what lets the product form
 * answer both languages from one choice: picking a colour in English names the
 * Arabic label at the same position, rather than asking the admin to find the
 * same option again.
 */
class SpecOptionsService
{
    /**
     * The label the option picked as `$label` in `$locale` carries in
     * `$otherLocale`. Null when there is nothing to answer with, which also
     * covers a choice being cleared.
     */
    public function mirrorLabel(
        TypeSpec $spec,
        mixed $label,
        string $locale,
        string $otherLocale,
    ): ?string {
        if (! is_scalar($label) || blank($label)) {
            return null;
        }

        $option = collect($spec->options ?? [])
            ->first(
                static fn (array $option): bool => ($option[$spec->optionKey($locale)] ?? null) === (string) $label,
            );

        $mirrored = $option[$spec->optionKey($otherLocale)] ?? null;

        return filled($mirrored) ? (string) $mirrored : null;
    }
}
