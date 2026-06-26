<?php

namespace App\Tenant\Auth\Http\Requests;

use App\Tenant\SystemSettings\Services\SystemSettingService;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $settings      = app(SystemSettingService::class);
        $passwordRules = ['required', 'string', 'min:'.$settings->integer('security.minimum_password_length', 8), 'confirmed'];

        if ($settings->boolean('security.require_strong_passwords')) {
            $passwordRules[] = 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/';
        }

        return [
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email', 'max:255'],
            'password' => $passwordRules,
        ];
    }

    public function messages(): array
    {
        return [
            'token.required'     => 'The password reset token is required.',
            'token.string'       => 'The password reset token is invalid.',
            'email.required'     => 'Enter your email address.',
            'email.email'        => 'Enter a valid email address.',
            'email.max'          => 'The email address may not exceed :max characters.',
            'password.required'  => 'Enter a new password.',
            'password.string'    => 'The new password must be text.',
            'password.min'       => 'The new password must be at least :min characters.',
            'password.confirmed' => 'The new password confirmation does not match.',
            'password.regex'     => 'The new password must include uppercase and lowercase letters, a number, and a special character.',
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
