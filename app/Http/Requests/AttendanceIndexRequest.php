<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceIndexRequest extends FormRequest
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
            'semester' => ['nullable', 'string', 'exists:semesters,semester'],
            'degree' => ['nullable', 'string', 'exists:degrees,name'],
            'faculty' => ['nullable', 'string', 'exists:faculties,name'],
            'topic' => ['nullable', 'string', Rule::in(array_keys(Attendance::topicOptions()))],
            'online' => ['nullable', 'boolean'],
            'from' => ['nullable', 'date'],
            'until' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }
}
