<?php

namespace App\Rules;

use App\Models\Attendance;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

class NotReservedDegreeName implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $normalizedName = Str::of($value)
            ->squish()
            ->lower()
            ->ascii()
            ->toString();
        $reservedName = Str::of(Attendance::DEGREE_UNSPECIFIED)
            ->squish()
            ->lower()
            ->ascii()
            ->toString();

        if ($normalizedName === $reservedName) {
            $fail(__('Dieser Name ist für die Auswahl „Keine Angabe“ reserviert.'));
        }
    }
}
