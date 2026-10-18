<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\View\ComponentAttributeBag;

/**
 * The wiring a text-like field cannot be drawn without: the id a label and an
 * error message point at, and the attributes the control itself carries. It
 * lives here rather than being copied into every field component, so a change
 * to how a field is described — a new ARIA attribute, a different id scheme —
 * is made once.
 */
final class FormFieldWiring
{
    /**
     * @return array{
     *     inputId: string,
     *     errorId: string,
     *     required: bool,
     *     hasError: bool,
     *     defaults: array<string, mixed>
     * }
     */
    public static function for(
        string $name,
        ?string $id,
        ComponentAttributeBag $attributes,
        FormFieldErrors $errors,
        ?string $value = null,
    ): array {
        $inputId = $id ?? 'field-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name);
        $errorId = $inputId.'-error';

        // Read by value rather than by presence: a field the caller turns off
        // still carries the attribute (`:required="false"`), and neither the
        // browser nor the label should treat it as required.
        $required = (bool) $attributes->get('required', false);
        $hasError = $errors->has($name);

        $defaults = ['id' => $inputId];

        if ($value !== null) {
            $defaults['value'] = $value;
        }

        // Describe the field by everything shown under it — the caller's hint(s)
        // first, then this field's error — so a screen reader reads both.
        $describedBy = array_filter([
            $attributes->get('aria-describedby'),
            $hasError ? $errorId : null,
        ]);

        if ($describedBy !== []) {
            $defaults['aria-describedby'] = implode(' ', $describedBy);
        }

        if ($hasError) {
            $defaults['aria-invalid'] = 'true';
        }

        return [
            'inputId' => $inputId,
            'errorId' => $errorId,
            'required' => $required,
            'hasError' => $hasError,
            'defaults' => $defaults,
        ];
    }
}
