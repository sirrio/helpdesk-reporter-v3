<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use App\Models\Degree;
use App\Models\Faculty;
use App\Models\Semester;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAttendanceRequest extends FormRequest
{
    private const ENTRY_TIMEZONE = 'Europe/Berlin';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|string>|string>
     */
    public function rules(): array
    {
        return [
            'semester' => [
                'required',
                'string',
                Rule::exists('semesters', 'semester')->whereNull('deleted_at'),
            ],
            'date' => ['required', Rule::date()->format('Y-m-d')],
            'startTime' => ['required', 'date_format:H:i'],
            'endTime' => ['required', 'date_format:H:i', 'after:startTime'],
            'degree' => [
                'required',
                'string',
                Rule::in($this->allowedDegreeValues()),
            ],
            'faculty' => [
                'required',
                'string',
                Rule::exists('faculties', 'name')->whereNull('deleted_at'),
            ],
            'topics' => ['required', 'array', 'min:1'],
            'topics.*' => ['string', Rule::in(array_keys(Attendance::topicOptions()))],
            'online' => ['required', 'boolean'],
            'visitors' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * Perform validation that depends on related records.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $date = $this->date('date');
                $semester = Semester::withTrashed()
                    ->where('semester', $this->string('semester')->toString())
                    ->first();
                $degree = Degree::withTrashed()
                    ->where('name', $this->string('degree')->toString())
                    ->first();
                $assignedFaculty = $degree?->faculty_id === null
                    ? null
                    : Faculty::withTrashed()->find($degree->faculty_id);
                $currentDateTime = CarbonImmutable::now(self::ENTRY_TIMEZONE);

                if ($semester !== null && ! $date->betweenIncluded($semester->start, $semester->end)) {
                    $validator->errors()->add(
                        'date',
                        __('Das Datum muss innerhalb des ausgewählten Semesters liegen.'),
                    );
                }

                if ($date->toDateString() > $currentDateTime->toDateString()) {
                    $validator->errors()->add(
                        'date',
                        __('Zukünftige Beratungen können nicht erfasst werden.'),
                    );
                }

                $endDateTime = CarbonImmutable::createFromFormat(
                    'Y-m-d H:i',
                    $date->toDateString().' '.$this->string('endTime')->toString(),
                    self::ENTRY_TIMEZONE,
                );

                if (
                    $date->toDateString() === $currentDateTime->toDateString()
                    && $endDateTime->isAfter($currentDateTime)
                ) {
                    $validator->errors()->add(
                        'endTime',
                        __('Die Endzeit darf nicht in der Zukunft liegen.'),
                    );
                }

                if ($degree?->faculty_id !== null && $assignedFaculty?->name !== $this->string('faculty')->toString()) {
                    $validator->errors()->add(
                        'faculty',
                        __('Der Fachbereich ist durch den Studiengang vorgegeben.'),
                    );
                }

                $attendance = $this->route('attendance');
                $ownerId = $attendance instanceof Attendance
                    ? $attendance->user_id
                    : $this->user()?->id;
                $duplicateExists = Attendance::query()
                    ->where('user_id', $ownerId)
                    ->whereDate('date', $date)
                    ->where('startTime', CarbonImmutable::createFromFormat(
                        'H:i',
                        $this->string('startTime')->toString(),
                    )->format('H:i:s'))
                    ->where('endTime', CarbonImmutable::createFromFormat(
                        'H:i',
                        $this->string('endTime')->toString(),
                    )->format('H:i:s'))
                    ->when(
                        $attendance instanceof Attendance,
                        fn ($query) => $query->whereKeyNot($attendance->getKey()),
                    )
                    ->exists();

                if ($duplicateExists) {
                    $validator->errors()->add(
                        'startTime',
                        __('Für diesen Zeitraum existiert bereits ein Eintrag.'),
                    );
                }
            },
        ];
    }

    /**
     * Get the selectable degree values, including an intentionally unspecified degree.
     *
     * @return array<int, string>
     */
    protected function allowedDegreeValues(): array
    {
        return Degree::query()
            ->orderBy('name')
            ->pluck('name')
            ->push(Attendance::DEGREE_UNSPECIFIED)
            ->all();
    }
}
