<?php

namespace App\Admin\Tenants\Http\Requests;

use App\Admin\Users\Models\User;
use App\Shared\Enums\UserType;
use App\Shared\SystemSettings\Services\SystemSettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TenantRequest extends FormRequest
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
        $routeParam = $this->route('tenant');
        $tenantId   = is_object($routeParam) ? $routeParam->id : $routeParam;
        $ownerId    = $this->ownerId($tenantId);
        $ownerPasswordRules = $this->ownerPasswordRules();

        return [
            'name'      => ['required', 'string', 'max:150'],
            'subdomain' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('tenants', 'subdomain')->ignore($tenantId)],
            'timezone'  => ['required', 'string', 'max:100', 'timezone'],
            'status'    => ['required', Rule::in(['active', 'inactive'])],
            'settings'  => ['nullable', 'array'],

            'owner_name'                  => ['required', 'string', 'max:255'],
            'owner_first_name'            => ['nullable', 'string', 'max:255'],
            'owner_last_name'             => ['nullable', 'string', 'max:255'],
            'owner_email'                 => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ownerId)],
            'owner_phone'                 => ['nullable', 'string', 'max:50'],
            'owner_password'              => $ownerPasswordRules,
            'owner_password_confirmation' => [$this->isMethod('post') ? 'required' : 'nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $tenantId = $this->route('tenant');

                if ($this->isMethod('put') && ! $this->ownerId($tenantId) && ! $this->filled('owner_password')) {
                    $validator->errors()->add('owner_password', 'The owner password field is required.');
                }
            },
        ];
    }

    public function prepareForValidation(): void
    {
        foreach (['name', 'timezone', 'status', 'owner_name', 'owner_first_name', 'owner_last_name', 'owner_email', 'owner_phone'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => trim((string) $this->input($field)) ?: null]);
            }
        }

        $subdomainSource = (string) $this->input('subdomain', $this->input('name', 'tenant'));

        $this->merge([
            'subdomain' => Str::slug($subdomainSource),
            'settings'  => $this->input('settings') ?? [],
            'status'    => $this->input('status', 'active'),
        ]);
    }

    private function ownerId(mixed $tenantId): ?int
    {
        if (! $tenantId || is_object($tenantId)) {
            return null;
        }

        return User::query()
            ->where('tenant_id', $tenantId)
            ->where('user_type', UserType::TENANT)
            ->oldest('id')
            ->value('id');
    }

    /**
     * @return array<int, string>
     */
    private function ownerPasswordRules(): array
    {
        $settings = app(SystemSettingService::class);
        $rules = [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'min:'.$settings->integer('security.minimum_password_length', 8), 'confirmed'];

        if ($settings->boolean('security.require_strong_passwords')) {
            $rules[] = 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/';
        }

        return $rules;
    }
}
