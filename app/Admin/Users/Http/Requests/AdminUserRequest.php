<?php

namespace App\Admin\Users\Http\Requests;

use App\Admin\SystemSettings\Services\SystemSettingService;
use App\Shared\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->user_type === UserType::ADMIN && (bool) Auth::user()?->is_active;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $routeParam = $this->route('adminUser');
        $userId     = is_object($routeParam) ? $routeParam->id : $routeParam;

        return [
            'name'                  => ['required', 'string', 'max:255'],
            'first_name'            => ['nullable', 'string', 'max:255'],
            'last_name'             => ['nullable', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone'                 => ['nullable', 'string', 'max:50'],
            'is_active'             => ['required', 'boolean'],
            'email_verified'        => ['required', 'boolean'],
            'password'              => $this->passwordRules(),
            'password_confirmation' => [$this->isMethod('post') ? 'required' : 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                  => 'Enter the admin user name.',
            'name.string'                    => 'The admin user name must be text.',
            'name.max'                       => 'The admin user name may not exceed :max characters.',
            'first_name.string'              => 'The first name must be text.',
            'first_name.max'                 => 'The first name may not exceed :max characters.',
            'last_name.string'               => 'The last name must be text.',
            'last_name.max'                  => 'The last name may not exceed :max characters.',
            'email.required'                 => 'Enter the admin user email address.',
            'email.email'                    => 'Enter a valid admin user email address.',
            'email.max'                      => 'The admin user email address may not exceed :max characters.',
            'email.unique'                   => 'This email address is already in use.',
            'phone.string'                   => 'The phone number must be text.',
            'phone.max'                      => 'The phone number may not exceed :max characters.',
            'is_active.required'             => 'Choose whether the admin user is active.',
            'is_active.boolean'              => 'The active setting must be true or false.',
            'email_verified.required'        => 'Choose whether the admin user email is verified.',
            'email_verified.boolean'         => 'The email verified setting must be true or false.',
            'password.required'              => 'Enter a password for the admin user.',
            'password.string'                => 'The password must be text.',
            'password.min'                   => 'The password must be at least :min characters.',
            'password.confirmed'             => 'The password confirmation does not match.',
            'password.regex'                 => 'The password must include uppercase and lowercase letters, a number, and a special character.',
            'password_confirmation.required' => 'Confirm the admin user password.',
            'password_confirmation.string'   => 'The password confirmation must be text.',
        ];
    }

    public function prepareForValidation(): void
    {
        foreach (['name', 'first_name', 'last_name', 'email', 'phone'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => trim((string) $this->input($field)) ?: null]);
            }
        }

        $this->merge([
            'is_active'      => $this->boolean('is_active', true),
            'email_verified' => $this->boolean('email_verified', true),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function passwordRules(): array
    {
        $settings = app(SystemSettingService::class);
        $rules    = [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'min:'.$settings->integer('security.minimum_password_length', 8), 'confirmed'];

        if ($settings->boolean('security.require_strong_passwords')) {
            $rules[] = 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/';
        }

        return $rules;
    }
}
