<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The admin panel's own sign-in and access check, with a refusal answered as
 * "no such page" rather than "not for you": a shopper who wanders onto /admin
 * should not be told that there is anything there to be kept out of.
 *
 * Everything else is Filament's middleware — this only changes the status a
 * refusal is served with, which is the one thing its check does not decide
 * for itself.
 */
class AuthenticateAdminPanel extends Authenticate
{
    #[\Override]
    protected function authenticate($request, array $guards): void
    {
        try {
            parent::authenticate($request, $guards);
        } catch (HttpException $refused) {
            // Its refusal is a 403; anything else is left as it is.
            throw $refused->getStatusCode() === 403
                ? new NotFoundHttpException(previous: $refused)
                : $refused;
        }
    }
}
