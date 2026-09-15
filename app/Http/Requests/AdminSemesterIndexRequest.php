<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminSemesterIndexRequest extends FormRequest
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
        return [
            'status' => ['nullable', 'string', Rule::in(['active', 'archived', 'all'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array{status?: string|null, page?: int|string|null}
     */
    public static function queryContext(Request $request): array
    {
        return Validator::make($request->query(), (new self)->rules())->validate();
    }
}
