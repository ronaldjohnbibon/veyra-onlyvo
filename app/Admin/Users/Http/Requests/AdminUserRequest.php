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
