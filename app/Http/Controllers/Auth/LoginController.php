<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\DTOs\FlashMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(
        LoginRequest $request,
        AuthService $auth,
    ): RedirectResponse {
        $credentials = $request->credentials();

        $user = $request->boolean('remember')
            ? $auth->attemptRemembering($credentials)
            : $auth->attempt($credentials);

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ])->errorBag('login');
        }

        return redirect()
            ->intended(route('home'))
            ->with(
                FlashMessage::success()
                    ->withTitle(__('auth.login.status', ['name' => $user->name]))
                    ->toSession(),
            );
    }
}
