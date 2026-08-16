<?php

namespace App\Rules;

use App\Models\Attendance;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
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

        if (Attendance::isUnspecifiedDegreeNameEquivalent($value)) {
            $fail(__('Dieser Name ist für die Auswahl „Keine Angabe“ reserviert.'));
        }
    }
}
