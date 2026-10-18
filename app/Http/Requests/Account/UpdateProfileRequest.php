<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\DTOs\ProfileData;
use App\Rules\PhoneNumberForCountry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
            'name' => [Rule::required(), Rule::string()->max(255)],
            'email' => [
                Rule::required(),
                Rule::string()->max(255),
                Rule::email(),
                Rule::unique('users', 'email')->ignore($this->user()?->getKey()),
            ],
            'locale' => [Rule::required(), Rule::string()->max(5), Rule::in(array_keys(config('locales')))],
            'profile_country_id' => [
                Rule::nullable(),
                Rule::requiredWith('phone_number'),
                Rule::numeric()->integer(),
                Rule::exists('countries', 'id'),
            ],
            'phone_number' => [
                Rule::nullable(),
                Rule::string()->max(32),
                new PhoneNumberForCountry(
                    $this->integer('profile_country_id'),
                ),
            ],
        ];
    }

    public function profile(): ProfileData
    {
        return ProfileData::fromArray($this->validated());
    }
}
