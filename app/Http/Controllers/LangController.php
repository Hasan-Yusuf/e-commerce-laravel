<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Account\AccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LangController extends Controller
{
    public function __construct(
        private AccountService $accountsService,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'language' => 'required|in:'.implode(',', array_keys(config('locales'))),
        ]);

        $locale = (string) $request->input('language');

        session()->put('locale', $locale);

        $user = $request->user();

        if ($user !== null) {
            $this->accountsService->rememberLocale($user, $locale);
        }

        return redirect()->back(fallback: route('home'));
    }
}
