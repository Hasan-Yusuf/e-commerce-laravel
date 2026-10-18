<?php

declare(strict_types=1);

namespace App\Http\Requests\Contact;

use App\DTOs\ContactMessageData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactMessageRequest extends FormRequest
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
            'email' => [Rule::required(), Rule::string()->max(255), Rule::email()],
            'subject' => [Rule::required(), Rule::string()->max(255)],
            'message' => [Rule::required(), Rule::string()->max(5000)],
            'website' => [Rule::prohibited()],
        ];
    }

    public function contactMessage(): ContactMessageData
    {
        return ContactMessageData::fromArray($this->validated());
    }
}
