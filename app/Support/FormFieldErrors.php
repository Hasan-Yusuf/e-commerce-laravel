<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\ViewErrorBag;

/**
 * The error-reporting context a form field renders with: the bag to read and
 * whether error reporting is enabled for this field at all. It is grouped into
 * one value object so the wiring helper does not grow a long, error-specific
 * parameter list.
 */
final readonly class FormFieldErrors
{
    public function __construct(
        public ViewErrorBag $errors,
        public string $bag = 'default',
        public bool $showErrors = true,
    ) {}

    /**
     * Whether the named field has a validation error to surface.
     */
    public function has(string $name): bool
    {
        return $this->showErrors && $this->errors->getBag($this->bag)->has($name);
    }
}
