<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\ToastType;
use Illuminate\Support\Str;

final readonly class FlashMessage
{
    public const string TITLE = 'status';

    public const string BODY = 'status_body';

    public const string TYPE = 'status_type';

    public string $title;

    public ?string $body;

    public function __construct(
        public ToastType $type,
        ?string $title = null,
        ?string $body = null,
    ) {
        $this->title = self::words($title);
        $this->body = self::blank(self::words($body));
    }

    public static function success(?string $title = null, ?string $body = null): self
    {
        return self::of(ToastType::SUCCESS)->withTitle($title)->withBody($body);
    }

    public static function warning(?string $title = null, ?string $body = null): self
    {
        return self::of(ToastType::WARNING)->withTitle($title)->withBody($body);
    }

    public static function info(?string $title = null, ?string $body = null): self
    {
        return self::of(ToastType::INFO)->withTitle($title)->withBody($body);
    }

    private static function of(ToastType $type): self
    {
        return new self($type);
    }

    public static function fromSession(): ?self
    {
        $title = self::words(session(self::TITLE));

        if ($title === '') {
            return null;
        }

        return self::of(ToastType::SUCCESS)
            ->withType(
                ToastType::fromValue(session(self::TYPE), ToastType::SUCCESS),
            )
            ->withTitle($title)
            ->withBody(self::words(session(self::BODY)));
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'title' => $this->title,
            'body' => $this->body,
        ];
    }

    public function toSession(): array
    {
        return array_filter(
            [
                self::TITLE => $this->title,
                self::TYPE => $this->type->value,
                self::BODY => $this->body,
            ],
            static fn (?string $part): bool => $part !== null && $part !== '',
        );
    }

    public function withType(ToastType $type): self
    {
        return $this->copy(['type' => $type]);
    }

    public function withTitle(?string $title): self
    {
        return $this->copy(['title' => $title]);
    }

    public function withBody(?string $body): self
    {
        return $this->copy(['body' => $body]);
    }

    private function copy(array $changes): self
    {
        return new self(
            ...array_merge(
                [
                    'type' => $this->type,
                    'title' => $this->title,
                    'body' => $this->body,
                ],
                $changes,
            ),
        );
    }

    private static function words(mixed $part): string
    {
        return is_string($part) ? Str::of($part)->squish()->toString() : '';
    }

    private static function blank(string $part): ?string
    {
        return $part === '' ? null : $part;
    }
}
