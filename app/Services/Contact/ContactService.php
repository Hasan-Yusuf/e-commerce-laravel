<?php

declare(strict_types=1);

namespace App\Services\Contact;

use App\DTOs\ContactMessageData;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ContactService
{
    public function store(ContactMessageData $data): ContactMessage
    {
        $message = ContactMessage::query()->create($data->toArray());

        $this->notifyStore($message);

        return $message;
    }

    private function notifyStore(ContactMessage $message): void
    {
        $address = config('mail.contact_address');

        $address = Str::of($address)->squish()->toString();

        if ($address === '') {
            return;
        }

        Mail::to($address)->send(new ContactMessageMail($message));
    }
}
