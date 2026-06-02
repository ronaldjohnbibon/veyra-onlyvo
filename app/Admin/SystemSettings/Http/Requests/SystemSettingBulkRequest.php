<?php

namespace App\Admin\SystemSettings\Http\Requests;

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
        return [
            'settings' => ['required', 'array'],

            'settings.general.application_name'        => ['required', 'string', 'max:150'],
            'settings.general.application_description' => ['nullable', 'string', 'max:1000'],
            'settings.general.logo'                    => ['nullable', 'string', 'max:2048'],
            'settings.general.favicon'                 => ['nullable', 'string', 'max:2048'],
            'settings.general.support_email'           => ['nullable', 'email', 'max:255'],
            'settings.general.support_phone'           => ['nullable', 'string', 'max:50'],
            'settings.general.company_address'         => ['nullable', 'string', 'max:1000'],

            'settings.authentication.allow_tenant_registration' => ['required', 'boolean'],
            'settings.authentication.require_email_verification' => ['required', 'boolean'],
            'settings.authentication.default_trial_days'         => ['required', 'integer', 'min:0', 'max:365'],

            'settings.security.session_lifetime_minutes'        => ['required', 'integer', 'min:5', 'max:10080'],
            'settings.security.login_rate_limit_attempts'       => ['required', 'integer', 'min:1', 'max:100'],
            'settings.security.login_rate_limit_window_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'settings.security.require_strong_passwords'        => ['required', 'boolean'],
            'settings.security.minimum_password_length'         => ['required', 'integer', 'min:8', 'max:128'],
            'settings.security.enable_admin_two_factor'         => ['required', 'boolean'],
            'settings.security.allowed_admin_ips'               => ['nullable', 'string', 'max:2000'],

            'settings.tenant_defaults.default_tenant_timezone'      => ['required', 'string', 'max:100', 'timezone'],
            'settings.tenant_defaults.default_tenant_status'        => ['required', Rule::in(['active', 'inactive'])],
            'settings.tenant_defaults.default_tenant_trial_days'    => ['required', 'integer', 'min:0', 'max:365'],
            'settings.tenant_defaults.default_tenant_template_type' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'settings.tenant_defaults.default_tenant_template_key'  => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],

            'settings.feature_flags.enable_templates_module'       => ['required', 'boolean'],
            'settings.feature_flags.enable_posts_module'           => ['required', 'boolean'],
            'settings.feature_flags.enable_analytics_module'       => ['required', 'boolean'],
            'settings.feature_flags.enable_design_requests_module' => ['required', 'boolean'],
            'settings.feature_flags.enable_cta_forms'              => ['required', 'boolean'],
            'settings.feature_flags.enable_tracking_logs'          => ['required', 'boolean'],

            'settings.email.mail_driver'   => ['required', 'string', Rule::in(['smtp', 'sendmail', 'mailgun', 'ses', 'ses-v2', 'postmark', 'log', 'array', 'failover', 'roundrobin'])],
            'settings.email.smtp_host'     => ['nullable', 'string', 'max:255'],
            'settings.email.smtp_port'     => ['nullable', 'integer', 'min:1', 'max:65535'],
            'settings.email.smtp_username' => ['nullable', 'string', 'max:255'],
            'settings.email.smtp_password' => ['nullable', 'string', 'max:255'],
            'settings.email.sender_name'   => ['required', 'string', 'max:150'],
            'settings.email.sender_email'  => ['required', 'email', 'max:255'],

            'settings.analytics.enable_visitor_tracking' => ['required', 'boolean'],
            'settings.analytics.enable_cta_tracking'     => ['required', 'boolean'],

            'settings.storage.maximum_upload_size' => ['required', 'integer', 'min:1', 'max:102400'],
            'settings.storage.allowed_file_types'  => ['required', 'string', 'max:500', 'regex:/^[a-z0-9,\\s]+$/i'],

            'settings.maintenance.maintenance_mode'    => ['required', 'boolean'],
            'settings.maintenance.maintenance_message' => ['nullable', 'string', 'max:1000'],
            'settings.maintenance.maintenance_start_time'    => ['nullable', 'date'],
            'settings.maintenance.maintenance_end_time'      => ['nullable', 'date'],
            'settings.maintenance.allow_admin_bypass'        => ['required', 'boolean'],
            'settings.maintenance.maintenance_affected_areas' => ['nullable', 'string', 'max:1000'],

            'settings.seo.default_meta_title'           => ['nullable', 'string', 'max:150'],
            'settings.seo.default_meta_description'     => ['nullable', 'string', 'max:500'],
            'settings.seo.open_graph_image'             => ['nullable', 'string', 'max:2048'],
            'settings.seo.allow_search_engine_indexing' => ['required', 'boolean'],
            'settings.seo.canonical_domain'             => ['nullable', 'string', 'max:255', 'regex:/^(https?:\\/\\/)?[a-z0-9][a-z0-9.-]*\\.[a-z]{2,}(?:\\/)?$/i'],

            'settings.compliance.privacy_policy_url'    => ['nullable', 'url', 'max:2048'],
            'settings.compliance.terms_of_service_url'  => ['nullable', 'url', 'max:2048'],
            'settings.compliance.cookie_notice_enabled' => ['required', 'boolean'],
            'settings.compliance.data_retention_days'   => ['required', 'integer', 'min:1', 'max:3650'],

            'settings.social.facebook_url'  => ['nullable', 'url', 'max:2048'],
            'settings.social.instagram_url' => ['nullable', 'url', 'max:2048'],
            'settings.social.linkedin_url'  => ['nullable', 'url', 'max:2048'],
            'settings.social.twitter_url'   => ['nullable', 'url', 'max:2048'],
            'settings.social.youtube_url'   => ['nullable', 'url', 'max:2048'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateAllowedAdminIps($validator);
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

    private function validateAllowedAdminIps(Validator $validator): void
    {
        $value = (string) $this->input('settings.security.allowed_admin_ips', '');

        if (trim($value) === '') {
            return;
        }

        $ips = preg_split('/[\s,]+/', $value) ?: [];

        foreach ($ips as $ip) {
            if ($ip === '' || $this->validIpOrCidr($ip)) {
                continue;
            }

            $validator->errors()->add('settings.security.allowed_admin_ips', 'Allowed admin IPs must contain valid IP addresses or CIDR ranges.');

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
}
