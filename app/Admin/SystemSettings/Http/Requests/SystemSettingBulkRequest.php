<?php

namespace App\Admin\SystemSettings\Http\Requests;

use App\Admin\SystemSettings\Services\SystemSettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SystemSettingBulkRequest extends FormRequest
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
        $rules = [
            'settings' => ['required', 'array'],
        ];

        foreach (app(SystemSettingService::class)->definitions() as $key => $definition) {
            $rules['settings.'.$key] = $this->rulesFor($key, $definition);
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateIpList($validator, 'security.allowed_admin_ips');
                $this->validateMaintenanceWindow($validator);
            },
        ];
    }

    public function prepareForValidation(): void
    {
        $settings = $this->input('settings', []);

        if (! is_array($settings)) {
            return;
        }

        array_walk_recursive($settings, function (&$value): void {
            if (is_string($value)) {
                $value = trim($value);
            }
        });

        $this->merge(['settings' => $settings]);
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<int, mixed>
     */
    private function rulesFor(string $key, array $definition): array
    {
        $type  = (string) $definition['type'];
        $rules = match ($type) {
            'boolean'  => ['required', 'boolean'],
            'integer'  => ['required', 'integer', 'min:0', 'max:100000'],
            'email'    => ['nullable', 'email', 'max:255'],
            'url'      => ['nullable', 'url', 'max:2048'],
            'image'    => ['nullable', 'string', 'max:2048'],
            'color'    => ['required', 'string', 'regex:/^#[0-9a-f]{6}$/i'],
            'text'     => ['nullable', 'string', 'max:10000'],
            'password' => ['nullable', 'string', 'max:5000'],
            default    => ['nullable', 'string', 'max:255'],
        };

        $rules = match ($key) {
            'general.application_name'                  => ['required', 'string', 'max:150'],
            'general.application_description'           => ['nullable', 'string', 'max:1000'],
            'general.support_phone'                     => ['nullable', 'string', 'max:50'],
            'general.company_address'                   => ['nullable', 'string', 'max:1000'],
            'security.session_lifetime_minutes'         => ['required', 'integer', 'min:5', 'max:10080'],
            'security.login_rate_limit_attempts'        => ['required', 'integer', 'min:1', 'max:100'],
            'security.login_rate_limit_window_minutes'  => ['required', 'integer', 'min:1', 'max:1440'],
            'security.minimum_password_length'          => ['required', 'integer', 'min:8', 'max:128'],
            'tenant_defaults.default_tenant_timezone'   => ['required', 'string', 'max:100', 'timezone'],
            'tenant_defaults.default_tenant_trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'tenant_defaults.default_tenant_template_type',
            'tenant_defaults.default_tenant_template_key' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'email.smtp_port'                             => ['nullable', 'integer', 'min:1', 'max:65535'],
            'email.sender_name'                           => ['required', 'string', 'max:150'],
            'email.sender_email'                          => ['required', 'email', 'max:255'],
            'storage.maximum_upload_size'                 => ['required', 'integer', 'min:1', 'max:102400'],
            'storage.allowed_file_types'                  => ['required', 'string', 'max:500', 'regex:/^[a-z0-9,\s]+$/i'],
            'maintenance.maintenance_message'             => ['nullable', 'string', 'max:1000'],
            'maintenance.maintenance_start_time',
            'maintenance.maintenance_end_time'       => ['nullable', 'date'],
            'maintenance.maintenance_affected_areas' => ['nullable', 'string', 'max:1000'],
            'seo.default_meta_title'                 => ['nullable', 'string', 'max:150'],
            'seo.default_meta_description'           => ['nullable', 'string', 'max:500'],
            'seo.canonical_domain'                   => ['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/)?[a-z0-9][a-z0-9.-]*\.[a-z]{2,}(?:\/)?$/i'],
            default                                  => $rules,
        };

        if ($type === 'select' && isset($definition['options']) && is_array($definition['options'])) {
            $rules = ['required', 'string', Rule::in(array_keys($definition['options']))];
        }

        return $rules;
    }

    private function validateIpList(Validator $validator, string $key): void
    {
        $value = (string) $this->input('settings.'.$key, '');

        foreach ($this->listFromText($value) as $ip) {
            if ($this->validIpOrCidr($ip)) {
                continue;
            }

            $validator->errors()->add('settings.'.$key, 'This field must contain valid IP addresses or CIDR ranges.');

            return;
        }
    }

    private function validateMaintenanceWindow(Validator $validator): void
    {
        $start = $this->input('settings.maintenance.maintenance_start_time');
        $end   = $this->input('settings.maintenance.maintenance_end_time');

        if (! $start || ! $end || strtotime((string) $end) >= strtotime((string) $start)) {
            return;
        }

        $validator->errors()->add('settings.maintenance.maintenance_end_time', 'Maintenance end time must be after the start time.');
    }

    private function validIpOrCidr(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_IP)) {
            return true;
        }

        [$ip, $prefix] = array_pad(explode('/', $value, 2), 2, null);

        if ($prefix === null || ! ctype_digit($prefix) || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        $maxPrefix = str_contains($ip, ':') ? 128 : 32;

        return (int) $prefix >= 0 && (int) $prefix <= $maxPrefix;
    }

    /**
     * @return array<int, string>
     */
    private function listFromText(string $value): array
    {
        return collect(preg_split('/[\s,]+/', $value) ?: [])
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }
}
