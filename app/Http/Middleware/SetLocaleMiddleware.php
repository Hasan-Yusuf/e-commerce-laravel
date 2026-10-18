<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * The locale may be stored in the session as either a plain locale code
     * (e.g. "en") or as the full config array from config/locales.php
     * (e.g. ['label' => 'English', 'dir' => 'ltr']). This normalises both
     * forms to a single code, applies it, and persists the normalised code
     * so the session always uses one shape. The resolved direction is stored
     * in the config so views (e.g. base.blade.php) can read it deterministically.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locales = config('locales', []);
        $fallback = config('app.locale');

        $locale = $this->resolveLocaleCode(
            session('locale', $fallback),
            $locales,
            $fallback,
        );

        if (\array_key_exists($locale, $locales)) {
            app()->setLocale($locale);
        }

        // Persist the normalised code so the session always uses one shape.
        if (session('locale') !== $locale) {
            session()->put('locale', $locale);
        }

        // Expose the direction for layouts that need to set dir="…" / lang="…".
        config()->set('app.locale_dir', $locales[$locale]['dir'] ?? 'ltr');

        return $next($request);
    }

    /**
     * Normalise the stored locale to its code, accepting both the plain string
     * form (e.g. "en") and the legacy array form that config() returns for a
     * locale — e.g. ['label' => 'English', 'dir' => 'ltr'] — where the code is
     * the key in config/locales.php and the value is the matching locale data.
     * Falls back to the application locale when neither matches a configured
     * locale.
     *
     * @param  array<string, mixed>|string|int  $stored
     * @param  array<string, array{label: string, dir: string}>  $locales
     */
    private function resolveLocaleCode(
        array|string|int $stored,
        array $locales,
        string $fallback,
    ): string {
        if (is_array($stored)) {
            // Reverse-lookup the code by matching the stored value against the
            // configured locale data for each code (e.g. ['label' => …, 'dir' => …]).
            foreach ($locales as $code => $details) {
                if ($stored === $details) {
                    return $code;
                }
            }

            // Otherwise accept [{code} => …] shaped arrays where the code is the key.
            $candidate = array_key_first($stored);

            return is_string($candidate) && \array_key_exists($candidate, $locales)
                ? $candidate
                : $fallback;
        }

        $candidate = (string) $stored;

        return \array_key_exists($candidate, $locales) ? $candidate : $fallback;
    }
}
