<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class AdminAttendanceIndexRequest extends AttendanceIndexRequest
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
     * @return array<string, array<int, \Illuminate\Contracts\Validation\ValidationRule|string>|string>
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
