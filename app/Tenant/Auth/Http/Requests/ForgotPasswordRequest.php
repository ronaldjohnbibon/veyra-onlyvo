<?php

namespace App\Tenant\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Enter your email address.',
            'email.email'    => 'Enter a valid email address.',
            'email.max'      => 'The email address may not exceed :max characters.',
        ];
    }

    public function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => trim((string) $this->input('email')) ?: null,
            ]);
        }
    }
}
