<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Resolves a class's translations through one method it may override, so a
 * resource, an enum, a dashboard widget, and a panel page all read their strings
 * the same way and the group is configured next to the class rather than
 * assembled ad hoc at every call site.
 */
trait HasTranslationKeys
{
    public static function trans(string $key, array $replace = []): string
    {
        return __((string) static::translationKeys().'.'.$key, $replace);
    }

    /**
     * Where this class reads its strings from. Override to point elsewhere, for
     * example a widget sharing the dashboard's file:
     *
     *     return TranslationKey::for(static::class)->forFilament()->setKey('dashboard');
     */
    protected static function translationKeys(): TranslationKey
    {
        return TranslationKey::for(static::class);
    }
}
