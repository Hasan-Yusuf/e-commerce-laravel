<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\ReadsCustomerDetails;
use Illuminate\Foundation\Http\Attributes\ErrorBag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * The register form is rendered twice on one page (the page itself and the auth
 * dialog in the layout), next to a login form that owns the same `email` and
 * `password` field names. Its errors therefore go in their own bag, so neither
 * the dialog's two panels nor the two copies can show each other's messages.
 */
#[ErrorBag('register')]
class RegisterRequest extends FormRequest
{
    use ReadsCustomerDetails;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * The password shape comes from the policy configured with
     * Password::defaults() (see AppServiceProvider); "confirmed" is the one
     * rule that belongs to the form rather than the policy.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [Rule::required(), Rule::string()->max(255)],
            'email' => [Rule::required(), Rule::string()->max(255), Rule::email(), Rule::unique('users', 'email')],
            'password' => [Rule::required(), Rule::confirmed(), Password::default()],
        ];
    }

    /**
     * The account to create: the name and email come from the shared customer
     * details (see ReadsCustomerDetails), so they are read the same way the
     * checkout reads them.
     *
     * @return array{name: string, email: string, password: string}
     */
    public function accountAttributes(): array
    {
        return [
            'name' => $this->customerName(),
            'email' => $this->customerEmail(),
            'password' => (string) $this->string('password'),
        ];
    }
}
