<?php

namespace App\Admin\Auth\Http\Requests;

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
            'email.required'    => 'Enter your admin email address.',
            'email.email'       => 'Enter a valid admin email address.',
            'password.required' => 'Enter your admin password.',
            'password.string'   => 'The admin password must be text.',
        ];
    }
}
