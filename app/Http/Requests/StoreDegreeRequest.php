<?php

namespace App\Http\Requests;

use App\Rules\NotReservedDegreeName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDegreeRequest extends FormRequest
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
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                new NotReservedDegreeName,
                Rule::unique('degrees', 'name'),
            ],
            'faculty_id' => ['required', 'integer', Rule::exists('faculties', 'id')->whereNull('deleted_at')],
        ];
    }
}
