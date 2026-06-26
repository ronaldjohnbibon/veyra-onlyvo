<?php

namespace App\Admin\Tenants\Http\Requests;

use App\Admin\SystemSettings\Services\SystemSettingService;
use App\Admin\Users\Models\User;
use App\Shared\Enums\UserType;
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
        $tenantId           = $this->routeTenantId();
        $ownerId            = $this->ownerId($tenantId);
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

    public function messages(): array
    {
        return [
            'name.required'                        => 'Enter the tenant name.',
            'name.string'                          => 'The tenant name must be text.',
            'name.max'                             => 'The tenant name may not exceed :max characters.',
            'subdomain.required'                   => 'Enter the tenant subdomain.',
            'subdomain.string'                     => 'The tenant subdomain must be text.',
            'subdomain.max'                        => 'The tenant subdomain may not exceed :max characters.',
            'subdomain.regex'                      => 'The tenant subdomain may contain only lowercase letters, numbers, and single hyphens.',
            'subdomain.unique'                     => 'This tenant subdomain is already in use.',
            'timezone.required'                    => 'Select the tenant timezone.',
            'timezone.string'                      => 'The tenant timezone must be text.',
            'timezone.max'                         => 'The tenant timezone may not exceed :max characters.',
            'timezone.timezone'                    => 'Select a valid tenant timezone.',
            'status.required'                      => 'Select the tenant status.',
            'status.in'                            => 'Select a valid tenant status.',
            'settings.array'                       => 'Tenant settings must be provided as a valid settings object.',
            'owner_name.required'                  => 'Enter the tenant owner name.',
            'owner_name.string'                    => 'The tenant owner name must be text.',
            'owner_name.max'                       => 'The tenant owner name may not exceed :max characters.',
            'owner_first_name.string'              => 'The owner first name must be text.',
            'owner_first_name.max'                 => 'The owner first name may not exceed :max characters.',
            'owner_last_name.string'               => 'The owner last name must be text.',
            'owner_last_name.max'                  => 'The owner last name may not exceed :max characters.',
            'owner_email.required'                 => 'Enter the tenant owner email address.',
            'owner_email.email'                    => 'Enter a valid tenant owner email address.',
            'owner_email.max'                      => 'The tenant owner email address may not exceed :max characters.',
            'owner_email.unique'                   => 'This owner email address is already in use.',
            'owner_phone.string'                   => 'The owner phone number must be text.',
            'owner_phone.max'                      => 'The owner phone number may not exceed :max characters.',
            'owner_password.required'              => 'Enter a password for the tenant owner.',
            'owner_password.string'                => 'The owner password must be text.',
            'owner_password.min'                   => 'The owner password must be at least :min characters.',
            'owner_password.confirmed'             => 'The owner password confirmation does not match.',
            'owner_password.regex'                 => 'The owner password must include uppercase and lowercase letters, a number, and a special character.',
            'owner_password_confirmation.required' => 'Confirm the tenant owner password.',
            'owner_password_confirmation.string'   => 'The owner password confirmation must be text.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $tenantId = $this->routeTenantId();

                if ($this->isMethod('put') && ! $this->ownerId($tenantId) && ! $this->filled('owner_password')) {
                    $validator->errors()->add('owner_password', 'Enter a password for the tenant owner.');
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

        $subdomainSource = (string) ($this->input('subdomain') ?: $this->input('name', 'tenant'));

        $this->merge([
            'subdomain' => Str::slug($subdomainSource),
            'settings'  => $this->input('settings') ?? [],
            'status'    => $this->input('status', 'active'),
        ]);
    }

    private function routeTenantId(): mixed
    {
        $routeParam = $this->route('tenant');

        return is_object($routeParam) ? $routeParam->id : $routeParam;
    }

    private function ownerId(mixed $tenantId): ?int
    {
        if (! $tenantId) {
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
        $rules    = [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'min:'.$settings->integer('security.minimum_password_length', 8), 'confirmed'];

        if ($settings->boolean('security.require_strong_passwords')) {
            $rules[] = 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/';
        }

        return $rules;
    }
}
