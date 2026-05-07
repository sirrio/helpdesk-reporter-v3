<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
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
     * @return array<string, array<int, \Illuminate\Contracts\Validation\ValidationRule|string>|string>
     */
    public function rules(): array
    {
        return [
            'semester' => ['required', 'string', 'exists:semesters,semester'],
            'date' => ['required', 'date'],
            'startTime' => ['required', 'date_format:H:i'],
            'endTime' => ['required', 'date_format:H:i', 'after:startTime'],
            'degree' => ['required', 'string', 'exists:degrees,name'],
            'faculty' => ['required', 'string', 'exists:faculties,name'],
            'topics' => ['required', 'array', 'min:1'],
            'topics.*' => ['string', Rule::in(array_keys(Attendance::topicOptions()))],
            'online' => ['required', 'boolean'],
        ];
    }
}
