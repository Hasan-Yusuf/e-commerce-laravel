<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\Attributes\ErrorBag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The login form is rendered twice on one page (the page itself and the auth
 * dialog in the layout), next to a register form that owns the same `email` and
 * `password` field names. Its errors therefore go in their own bag, so neither
 * the dialog's two panels nor the two copies can show each other's messages.
 */
#[ErrorBag('login')]
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Only the shape of the credentials is validated here; whether they match
     * an account is decided by the auth service, so a wrong password and an
     * unknown address produce the same, generic error.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'email' => [Rule::required(), Rule::string()->max(255), Rule::email()],
            'password' => [Rule::required(), Rule::string()],
            'remember' => [Rule::nullable(), Rule::boolean()],
        ];
    }

    /**
     * @return array{email: string, password: string}
     */
    public function credentials(): array
    {
        return [
            'email' => (string) $this->string('email'),
            'password' => (string) $this->string('password'),
        ];
    }
}
