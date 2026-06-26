<?php

namespace App\Tenant\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Enter your email address.',
            'email.email'       => 'Enter a valid email address.',
            'password.required' => 'Enter your password.',
            'password.string'   => 'The password must be text.',
        ];
    }
}
