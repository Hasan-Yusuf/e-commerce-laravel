<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class ResponsiveImageData
{
    public function __construct(
        public string $src,
        public ?string $srcset = null,
    ) {}
}
