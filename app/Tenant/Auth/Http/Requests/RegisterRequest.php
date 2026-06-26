<?php

namespace App\Tenant\Auth\Http\Requests;

use App\Tenant\SystemSettings\Services\SystemSettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone'     => ['required', 'regex:/^09\d{9}$/'],
            'password'  => $passwordRules,
            'name'      => ['required', 'string', 'max:255', Rule::unique('tenants', 'name')],
            'subdomain' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('tenants', 'subdomain')],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'     => 'Enter your email address.',
            'email.email'        => 'Enter a valid email address.',
            'email.max'          => 'The email address may not exceed :max characters.',
            'email.unique'       => 'An account with this email address already exists.',
            'phone.required'     => 'Enter your phone number.',
            'phone.regex'        => 'Enter a valid Philippine mobile number beginning with 09.',
            'password.required'  => 'Enter a password.',
            'password.string'    => 'The password must be text.',
            'password.min'       => 'The password must be at least :min characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.regex'     => 'The password must include uppercase and lowercase letters, a number, and a special character.',
            'name.required'      => 'Enter your company name.',
            'name.string'        => 'The company name must be text.',
            'name.max'           => 'The company name may not exceed :max characters.',
            'name.unique'        => 'A company with this name already exists.',
            'subdomain.required' => 'The company name must contain letters or numbers that can be used for the workspace subdomain.',
            'subdomain.string'   => 'The workspace subdomain must be text.',
            'subdomain.max'      => 'The workspace subdomain may not exceed :max characters.',
            'subdomain.regex'    => 'The company name must create a valid workspace subdomain.',
            'subdomain.unique'   => 'A workspace with a matching company subdomain already exists.',
        ];
    }

    public function prepareForValidation(): void
    {
        foreach (['email', 'phone', 'name'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => trim((string) $this->input($field)) ?: null]);
            }
        }

        $this->merge([
            'subdomain' => Str::slug((string) $this->input('name')),
        ]);
    }
}
