<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Support\Str;

final readonly class ContactMessageData
{
    public function __construct(
        public string $name = '',
        public string $email = '',
        public string $subject = '',
        public string $message = '',
    ) {}

    public static function fromArray(mixed $data): self
    {
        $data = is_array($data) ? $data : [];

        return new self(
            name: trim((string) Str::of($data['name'])->squish()),
            email: trim((string) Str::of($data['email'])->squish()),
            subject: trim((string) Str::of($data['subject'])->squish()),
            message: trim((string) Str::of($data['message'])->squish()),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'subject' => $this->subject,
            'message' => $this->message,
        ];
    }
}
