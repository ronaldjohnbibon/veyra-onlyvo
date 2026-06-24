<?php

namespace App\Tenant\SystemSettings\Http\Requests;

use App\Tenant\SystemSettings\Services\TenantSystemSettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantSystemSettingBulkRequest extends FormRequest
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

        foreach (app(TenantSystemSettingService::class)->definitions() as $key => $definition) {
            $settingRules = $this->rulesFor($key, $definition);

            if (str_starts_with($key, 'branding.')) {
                array_unshift($settingRules, 'sometimes');
            }

            $rules['settings.'.$key] = $settingRules;
        }

        return $rules;
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
        $type = (string) $definition['type'];

        $rules = match ($type) {
            'boolean' => ['required', 'boolean'],
            'integer' => ['required', 'integer', 'min:0', 'max:100000'],
            'email'   => ['nullable', 'email', 'max:255'],
            'url'     => ['nullable', 'url', 'max:2048'],
            'image'   => ['nullable', 'string', 'max:2048'],
            'color'   => ['required', 'string', 'regex:/^#[0-9a-f]{6}$/i'],
            'text'    => ['nullable', 'string', 'max:10000'],
            default   => ['nullable', 'string', 'max:255'],
        };

        $rules = match ($key) {
            'profile.business_name'         => ['required', 'string', 'max:150'],
            'profile.description'           => ['nullable', 'string', 'max:1000'],
            'profile.timezone'              => ['required', 'string', 'max:100', 'timezone'],
            'profile.contact_phone'         => ['nullable', 'string', 'max:50'],
            'profile.contact_address'       => ['nullable', 'string', 'max:1000'],
            'website.homepage_slug'         => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'website.primary_cta_label'     => ['nullable', 'string', 'max:80'],
            'website.footer_text'           => ['nullable', 'string', 'max:1000'],
            'seo.default_meta_title'        => ['nullable', 'string', 'max:150'],
            'seo.default_meta_description'  => ['nullable', 'string', 'max:500'],
            'seo.canonical_domain'          => ['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/)?[a-z0-9][a-z0-9.-]*\.[a-z]{2,}(?:\/)?$/i'],
            'analytics.retention_days'      => ['required', 'integer', 'min:1', 'max:3650'],
            'compliance.cookie_notice_text' => ['nullable', 'string', 'max:1000'],
            default                         => $rules,
        };

        if ($type === 'select' && isset($definition['options']) && is_array($definition['options'])) {
            $rules = ['required', 'string', Rule::in(array_keys($definition['options']))];
        }

        return $rules;
    }
}
