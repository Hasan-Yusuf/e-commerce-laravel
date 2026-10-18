<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The image types this application takes and derives from.
 *
 * An upload is stored as the file the administrator chose; what the storefront
 * serves is a converted copy of it (see `HasImageConversions`), which is why
 * the conversion this app asks for — WebP — is a case here rather than a string
 * in a model or a form.
 */
enum MimeType: string
{
    case IMAGE_JPEG = 'image/jpeg';
    case IMAGE_PNG = 'image/png';
    case IMAGE_GIF = 'image/gif';
    case IMAGE_BMP = 'image/bmp';
    case IMAGE_WEBP = 'image/webp';
    case IMAGE_AVIF = 'image/avif';

    /**
     * The extension the type is written with; also the format the image driver
     * is asked to write.
     */
    public function extension(): string
    {
        return match ($this) {
            self::IMAGE_JPEG => 'jpg',
            self::IMAGE_PNG => 'png',
            self::IMAGE_GIF => 'gif',
            self::IMAGE_BMP => 'bmp',
            self::IMAGE_WEBP => 'webp',
            self::IMAGE_AVIF => 'avif',
        };
    }

    /**
     * The name a conversion derived from this type carries: its extension, so a
     * conversion can be asked for by name without a second string to keep in
     * step.
     */
    public function conversion(): string
    {
        return $this->extension();
    }

    /**
     * The types as an upload field accepts them, so what an administrator may
     * choose and what this app can handle are one list.
     *
     * @return array<int, string>
     */
    public static function imageValues(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            self::cases(),
        );
    }
}
