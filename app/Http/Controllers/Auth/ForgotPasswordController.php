<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Placeholder page: the password_reset_tokens table already exists, so a real
 * reset flow can be added later without any schema change.
 */
final class ForgotPasswordController extends Controller
{
    public function __invoke(): View
    {
        return view('auth.forgot-password');
    }
}
