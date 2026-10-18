<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Country;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class PhoneNumberForCountry implements ValidationRule
{
    public function __construct(
        protected ?int $countryId,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    #[\Override]
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail,
    ): void {
        if ($value === null || trim((string) $value) === '') {
            return;
        }

        $number = trim((string) $value);

        if (! ctype_digit($number)) {
            $fail('validation.phone_number_digits')->translate();

            return;
        }

        $length = $this->phoneLength();

        if ($length !== null && \strlen($number) !== $length) {
            $fail('validation.phone_number_length')->translate([
                'count' => $length,
            ]);
        }
    }

    private function phoneLength(): ?int
    {
        if ($this->countryId === null || $this->countryId <= 0) {
            return null;
        }

        $length = Country::query()
            ->whereKey($this->countryId)
            ->value('phone_length');

        return $length === null ? null : (int) $length;
    }
}
