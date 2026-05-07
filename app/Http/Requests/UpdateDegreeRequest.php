<?php

namespace App\Http\Requests;

use App\Models\Degree;
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
                Rule::unique('degrees', 'name')->ignore($degree),
            ],
        ];
    }
}
