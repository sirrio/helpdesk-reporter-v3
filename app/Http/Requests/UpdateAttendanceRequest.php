<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class UpdateAttendanceRequest extends StoreAttendanceRequest
{
    public function authorize(): bool
    {
        $attendance = $this->route('attendance');

        return $attendance instanceof Attendance
            && ($attendance->user_id === $this->user()?->id
                || $this->user()?->isMod === true
                || $this->user()?->isAdmin === true);
    }

    /**
     * Allow an attendance to retain archived historical reference values.
     *
     * @return array<string, array<int, ValidationRule|string>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $attendance = $this->route('attendance');

        if (! $attendance instanceof Attendance) {
            return $rules;
        }

        $rules['semester'] = $this->activeOrCurrentExistsRule(
            'semesters',
            'semester',
            $attendance->semester,
        );
        $rules['faculty'] = $this->activeOrCurrentExistsRule(
            'faculties',
            'name',
            $attendance->faculty,
        );

        return $rules;
    }

    /**
     * Allow an attendance to retain an archived historical degree value.
     *
     * @return array<int, string>
     */
    protected function allowedDegreeValues(): array
    {
        $values = parent::allowedDegreeValues();
        $attendance = $this->route('attendance');

        if ($attendance instanceof Attendance) {
            $values[] = $attendance->degree;
        }

        return array_values(array_unique($values));
    }

    /**
     * Require an active record unless the historical value remains unchanged.
     *
     * @return array<int, ValidationRule|string>
     */
    private function activeOrCurrentExistsRule(string $table, string $column, string $currentValue): array
    {
        return [
            'required',
            'string',
            Rule::exists($table, $column)->where(
                fn (Builder $query): Builder => $query
                    ->whereNull('deleted_at')
                    ->orWhere($column, $currentValue),
            ),
        ];
    }
}
