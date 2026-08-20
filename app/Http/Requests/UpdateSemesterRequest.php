<?php

namespace App\Http\Requests;

use App\Models\Semester;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSemesterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|string>|string>
     */
    public function rules(): array
    {
        /** @var Semester $semester */
        $semester = $this->route('semester');

        return [
            'semester' => [
                'required',
                'string',
                'max:255',
                Rule::unique('semesters', 'semester')->ignore($semester->id),
            ],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ];
    }
}
