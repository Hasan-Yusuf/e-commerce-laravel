<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\DTOs\FlashMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(
        RegisterRequest $request,
        AuthService $auth,
    ): RedirectResponse {
        $auth->register($request->accountAttributes());

        return redirect()
            ->intended(route('home'))
            ->with(
                FlashMessage::success()
                    ->withTitle(__('auth.register.status'))
                    ->toSession(),
            );
    }
}
