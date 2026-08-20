<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class AdminAttendanceIndexRequest extends AttendanceIndexRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isMod === true || $this->user()?->isAdmin === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|string>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'user' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'week' => ['nullable', Rule::date()->format('Y-m-d')],
        ];
    }
}
