<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\DTOs\FlashMessage;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class LogoutController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with(
                FlashMessage::success()
                    ->withTitle(__('auth.logout.status'))
                    ->toSession(),
            );
    }
}
