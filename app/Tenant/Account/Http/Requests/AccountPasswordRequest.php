<?php

namespace App\Tenant\Account\Http\Requests;

use App\Tenant\SystemSettings\Services\SystemSettingService;
use Illuminate\Foundation\Http\FormRequest;

class AccountPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $settings      = app(SystemSettingService::class);
        $passwordRules = ['required', 'string', 'min:'.$settings->integer('security.minimum_password_length', 8), 'confirmed'];

        if ($settings->boolean('security.require_strong_passwords')) {
            $passwordRules[] = 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/';
        }

        return [
            'current_password' => ['required', 'string'],
            'password'         => $passwordRules,
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Enter your current password.',
            'current_password.string'   => 'The current password must be text.',
            'password.required'         => 'Enter a new password.',
            'password.string'           => 'The new password must be text.',
            'password.min'              => 'The new password must be at least :min characters.',
            'password.confirmed'        => 'The new password confirmation does not match.',
            'password.regex'            => 'The new password must include uppercase and lowercase letters, a number, and a special character.',
        ];
    }
}
