<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateAdminUserRequest extends FormRequest
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
        /** @var User $managedUser */
        $managedUser = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($managedUser->id),
            ],
            'password' => ['nullable', 'string', Password::default()],
            'isMod' => ['required', 'boolean'],
            'isAdmin' => ['required', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var User $managedUser */
                $managedUser = $this->route('user');

                if (
                    $managedUser->is($this->user())
                    && ! $this->boolean('isAdmin')
                ) {
                    $validator->errors()->add(
                        'isAdmin',
                        'Du kannst dir selbst die Admin-Rechte nicht entziehen.',
                    );
                }
            },
        ];
    }
}
