<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * A password change. The current password is what makes this form the account
 * owner's rather than anyone holding a live session's, and the new one is
 * validated against the same policy registration does (see AppServiceProvider).
 */
class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'current_password' => [Rule::required(), Rule::currentPassword()],
            'password' => [Rule::required(), Rule::confirmed(), Password::default()],
        ];
    }
}
