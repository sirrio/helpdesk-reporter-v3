<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use App\Models\Degree;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDegreeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Degree $degree */
        $degree = $this->route('degree');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::notIn([Attendance::DEGREE_UNSPECIFIED]),
                Rule::unique('degrees', 'name')->ignore($degree),
            ],
            'faculty_id' => ['required', 'integer', Rule::exists('faculties', 'id')->whereNull('deleted_at')],
        ];
    }
}
