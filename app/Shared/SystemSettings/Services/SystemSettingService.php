<?php

namespace App\Shared\SystemSettings\Services;

use App\Shared\SystemSettings\Models\SystemSetting;
use App\Shared\SystemSettings\Models\SystemSettingHistory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\IpUtils;

class SystemSettingService
{
    public const CACHE_KEY = 'system_settings.values';

    /**
     * @var array<string, array{group: string, label: string, type: string, default: mixed, public: bool}>
     */
    private const DEFINITIONS = [
        'general.application_name'        => ['group' => 'general', 'label' => 'Application Name', 'type' => 'string', 'default' => 'Onlyvo', 'public' => true],
        'general.application_description' => ['group' => 'general', 'label' => 'Application Description', 'type' => 'text', 'default' => 'Onlyvo tenant platform', 'public' => true],
        'general.logo'                    => ['group' => 'general', 'label' => 'Logo', 'type' => 'string', 'default' => '', 'public' => true],
        'general.favicon'                 => ['group' => 'general', 'label' => 'Favicon', 'type' => 'string', 'default' => '', 'public' => true],
        'general.support_email'           => ['group' => 'general', 'label' => 'Support Email', 'type' => 'string', 'default' => '', 'public' => true],
        'general.support_phone'           => ['group' => 'general', 'label' => 'Support Phone', 'type' => 'string', 'default' => '', 'public' => true],
        'general.company_address'         => ['group' => 'general', 'label' => 'Company Address', 'type' => 'text', 'default' => '', 'public' => true],

        'authentication.allow_tenant_registration' => ['group' => 'authentication', 'label' => 'Allow Tenant Registration', 'type' => 'boolean', 'default' => true, 'public' => false],
        'authentication.require_email_verification' => ['group' => 'authentication', 'label' => 'Require Email Verification', 'type' => 'boolean', 'default' => false, 'public' => false],
        'authentication.default_trial_days'         => ['group' => 'authentication', 'label' => 'Default Trial Days', 'type' => 'integer', 'default' => 14, 'public' => false],

        'security.session_lifetime_minutes'        => ['group' => 'security', 'label' => 'Session Lifetime Minutes', 'type' => 'integer', 'default' => 120, 'public' => false],
        'security.login_rate_limit_attempts'       => ['group' => 'security', 'label' => 'Login Rate Limit Attempts', 'type' => 'integer', 'default' => 5, 'public' => false],
        'security.login_rate_limit_window_minutes' => ['group' => 'security', 'label' => 'Login Rate Limit Window Minutes', 'type' => 'integer', 'default' => 1, 'public' => false],
        'security.require_strong_passwords'        => ['group' => 'security', 'label' => 'Require Strong Passwords', 'type' => 'boolean', 'default' => false, 'public' => false],
        'security.minimum_password_length'         => ['group' => 'security', 'label' => 'Minimum Password Length', 'type' => 'integer', 'default' => 8, 'public' => false],
        'security.enable_admin_two_factor'         => ['group' => 'security', 'label' => 'Enable Admin Two-Factor Authentication', 'type' => 'boolean', 'default' => false, 'public' => false],
        'security.allowed_admin_ips'               => ['group' => 'security', 'label' => 'Allowed Admin IPs', 'type' => 'text', 'default' => '', 'public' => false],

        'tenant_defaults.default_tenant_timezone'      => ['group' => 'tenant_defaults', 'label' => 'Default Tenant Timezone', 'type' => 'string', 'default' => 'UTC', 'public' => false],
        'tenant_defaults.default_tenant_status'        => ['group' => 'tenant_defaults', 'label' => 'Default Tenant Status', 'type' => 'string', 'default' => 'active', 'public' => false],
        'tenant_defaults.default_tenant_trial_days'    => ['group' => 'tenant_defaults', 'label' => 'Default Tenant Trial Days', 'type' => 'integer', 'default' => 14, 'public' => false],
        'tenant_defaults.default_tenant_template_type' => ['group' => 'tenant_defaults', 'label' => 'Default Tenant Template Type', 'type' => 'string', 'default' => '', 'public' => false],
        'tenant_defaults.default_tenant_template_key'  => ['group' => 'tenant_defaults', 'label' => 'Default Tenant Template Key', 'type' => 'string', 'default' => '', 'public' => false],

        'feature_flags.enable_templates_module'       => ['group' => 'feature_flags', 'label' => 'Enable Templates Module', 'type' => 'boolean', 'default' => true, 'public' => true],
        'feature_flags.enable_posts_module'           => ['group' => 'feature_flags', 'label' => 'Enable Posts Module', 'type' => 'boolean', 'default' => true, 'public' => true],
        'feature_flags.enable_analytics_module'       => ['group' => 'feature_flags', 'label' => 'Enable Analytics Module', 'type' => 'boolean', 'default' => true, 'public' => true],
        'feature_flags.enable_design_requests_module' => ['group' => 'feature_flags', 'label' => 'Enable Design Requests Module', 'type' => 'boolean', 'default' => true, 'public' => true],
        'feature_flags.enable_cta_forms'              => ['group' => 'feature_flags', 'label' => 'Enable CTA Forms', 'type' => 'boolean', 'default' => true, 'public' => true],
        'feature_flags.enable_tracking_logs'          => ['group' => 'feature_flags', 'label' => 'Enable Tracking Logs', 'type' => 'boolean', 'default' => true, 'public' => true],

        'email.mail_driver'   => ['group' => 'email', 'label' => 'Mail Driver', 'type' => 'string', 'default' => 'smtp', 'public' => false],
        'email.smtp_host'     => ['group' => 'email', 'label' => 'SMTP Host', 'type' => 'string', 'default' => '', 'public' => false],
        'email.smtp_port'     => ['group' => 'email', 'label' => 'SMTP Port', 'type' => 'integer', 'default' => 587, 'public' => false],
        'email.smtp_username' => ['group' => 'email', 'label' => 'SMTP Username', 'type' => 'string', 'default' => '', 'public' => false],
        'email.smtp_password' => ['group' => 'email', 'label' => 'SMTP Password', 'type' => 'password', 'default' => '', 'public' => false],
        'email.sender_name'   => ['group' => 'email', 'label' => 'Sender Name', 'type' => 'string', 'default' => 'Onlyvo', 'public' => false],
        'email.sender_email'  => ['group' => 'email', 'label' => 'Sender Email', 'type' => 'string', 'default' => 'hello@example.com', 'public' => false],

        'analytics.enable_visitor_tracking' => ['group' => 'analytics', 'label' => 'Enable Visitor Tracking', 'type' => 'boolean', 'default' => true, 'public' => false],
        'analytics.enable_cta_tracking'     => ['group' => 'analytics', 'label' => 'Enable CTA Tracking', 'type' => 'boolean', 'default' => true, 'public' => false],

        'storage.maximum_upload_size' => ['group' => 'storage', 'label' => 'Maximum Upload Size', 'type' => 'integer', 'default' => 4096, 'public' => false],
        'storage.allowed_file_types'  => ['group' => 'storage', 'label' => 'Allowed File Types', 'type' => 'string', 'default' => 'jpg,jpeg,png,webp,gif', 'public' => false],

        'maintenance.maintenance_mode'    => ['group' => 'maintenance', 'label' => 'Maintenance Mode', 'type' => 'boolean', 'default' => false, 'public' => true],
        'maintenance.maintenance_message' => ['group' => 'maintenance', 'label' => 'Maintenance Message', 'type' => 'text', 'default' => 'The platform is temporarily unavailable for maintenance.', 'public' => true],
        'maintenance.maintenance_start_time'    => ['group' => 'maintenance', 'label' => 'Maintenance Start Time', 'type' => 'string', 'default' => '', 'public' => true],
        'maintenance.maintenance_end_time'      => ['group' => 'maintenance', 'label' => 'Maintenance End Time', 'type' => 'string', 'default' => '', 'public' => true],
        'maintenance.allow_admin_bypass'        => ['group' => 'maintenance', 'label' => 'Allow Admin Bypass', 'type' => 'boolean', 'default' => true, 'public' => true],
        'maintenance.maintenance_affected_areas' => ['group' => 'maintenance', 'label' => 'Maintenance Affected Areas', 'type' => 'text', 'default' => '', 'public' => true],

        'seo.default_meta_title'          => ['group' => 'seo', 'label' => 'Default Meta Title', 'type' => 'string', 'default' => 'Onlyvo', 'public' => true],
        'seo.default_meta_description'    => ['group' => 'seo', 'label' => 'Default Meta Description', 'type' => 'text', 'default' => 'Onlyvo tenant platform', 'public' => true],
        'seo.open_graph_image'            => ['group' => 'seo', 'label' => 'Open Graph Image', 'type' => 'string', 'default' => '', 'public' => true],
        'seo.allow_search_engine_indexing' => ['group' => 'seo', 'label' => 'Allow Search Engine Indexing', 'type' => 'boolean', 'default' => true, 'public' => true],
        'seo.canonical_domain'            => ['group' => 'seo', 'label' => 'Canonical Domain', 'type' => 'string', 'default' => '', 'public' => true],

        'compliance.privacy_policy_url'    => ['group' => 'compliance', 'label' => 'Privacy Policy URL', 'type' => 'string', 'default' => '', 'public' => true],
        'compliance.terms_of_service_url'  => ['group' => 'compliance', 'label' => 'Terms of Service URL', 'type' => 'string', 'default' => '', 'public' => true],
        'compliance.cookie_notice_enabled' => ['group' => 'compliance', 'label' => 'Cookie Notice Enabled', 'type' => 'boolean', 'default' => false, 'public' => true],
        'compliance.data_retention_days'   => ['group' => 'compliance', 'label' => 'Data Retention Days', 'type' => 'integer', 'default' => 365, 'public' => false],

        'social.facebook_url'  => ['group' => 'social', 'label' => 'Facebook URL', 'type' => 'string', 'default' => '', 'public' => true],
        'social.instagram_url' => ['group' => 'social', 'label' => 'Instagram URL', 'type' => 'string', 'default' => '', 'public' => true],
        'social.linkedin_url'  => ['group' => 'social', 'label' => 'LinkedIn URL', 'type' => 'string', 'default' => '', 'public' => true],
        'social.twitter_url'   => ['group' => 'social', 'label' => 'X/Twitter URL', 'type' => 'string', 'default' => '', 'public' => true],
        'social.youtube_url'   => ['group' => 'social', 'label' => 'YouTube URL', 'type' => 'string', 'default' => '', 'public' => true],
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        return self::DEFINITIONS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function groups(bool $publicOnly = false): array
    {
        $values = $this->values($publicOnly);
        $groups = [];

        foreach (self::DEFINITIONS as $key => $definition) {
            if ($publicOnly && ! $definition['public']) {
                continue;
            }

            $group = $definition['group'];

            $groups[$group] ??= [
                'key'      => $group,
                'label'    => $this->groupLabel($group),
                'settings' => [],
            ];

            $groups[$group]['settings'][] = [
                'key'       => $key,
                'name'      => str($key)->after('.')->value(),
                'label'     => $definition['label'],
                'type'      => $definition['type'],
                'value'     => $values[$key] ?? $definition['default'],
                'is_public' => $definition['public'],
            ];
        }

        return array_values($groups);
    }

    /**
     * @return array<string, mixed>
     */
    public function values(bool $publicOnly = false): array
    {
        $values = $this->storedValues();

        return collect(self::DEFINITIONS)
            ->when($publicOnly, fn ($definitions) => $definitions->filter(fn ($definition) => $definition['public']))
            ->mapWithKeys(function (array $definition, string $key) use ($values): array {
                return [$key => $values[$key] ?? $definition['default']];
            })
            ->all();
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        $definition = self::DEFINITIONS[$key] ?? null;

        if (! $definition) {
            return $fallback;
        }

        return $this->values()[$key] ?? $definition['default'] ?? $fallback;
    }

    public function string(string $key, string $fallback = ''): string
    {
        return trim((string) $this->get($key, $fallback));
    }

    public function integer(string $key, int $fallback = 0): int
    {
        return (int) $this->get($key, $fallback);
    }

    public function boolean(string $key, bool $fallback = false): bool
    {
        return filter_var($this->get($key, $fallback), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, SystemSetting>
     */
    public function upsertGrouped(array $payload, ?Authenticatable $actor = null): array
    {
        $saved = [];

        foreach ($this->flattenGroupedPayload($payload) as $key => $value) {
            $saved[] = $this->upsert($key, $value, $actor);
        }

        Cache::forget(self::CACHE_KEY);

        return $saved;
    }

    public function upsert(string $key, mixed $value, ?Authenticatable $actor = null): SystemSetting
    {
        $definition = self::DEFINITIONS[$key];
        $existing   = SystemSetting::query()->where('key', $key)->first();
        $newValue   = $this->castValue($definition['type'], $value);

        $setting = SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'group'     => $definition['group'],
                'label'     => $definition['label'],
                'type'      => $definition['type'],
                'value'     => $newValue,
                'is_public' => $definition['public'],
            ],
        );

        if (! $existing || $existing->value !== $newValue) {
            $this->recordHistory($key, $existing?->value, $newValue, $actor);
        }

        Cache::forget(self::CACHE_KEY);

        return $setting;
    }

    public function delete(SystemSetting $setting): void
    {
        $setting->delete();
        Cache::forget(self::CACHE_KEY);
    }

    public function applyRuntimeConfig(): void
    {
        if (! $this->tableExists()) {
            return;
        }

        config([
            'app.name'                 => $this->string('general.application_name', (string) config('app.name')),
            'mail.default'             => $this->string('email.mail_driver', (string) config('mail.default')),
            'mail.from.name'           => $this->string('email.sender_name', (string) config('mail.from.name')),
            'mail.from.address'        => $this->string('email.sender_email', (string) config('mail.from.address')),
            'mail.mailers.smtp.host'   => $this->string('email.smtp_host', (string) config('mail.mailers.smtp.host')),
            'mail.mailers.smtp.port'   => $this->integer('email.smtp_port', (int) config('mail.mailers.smtp.port')),
            'mail.mailers.smtp.username' => $this->string('email.smtp_username', (string) config('mail.mailers.smtp.username')),
            'mail.mailers.smtp.password' => $this->string('email.smtp_password', (string) config('mail.mailers.smtp.password')),
            'session.lifetime'           => $this->integer('security.session_lifetime_minutes', (int) config('session.lifetime')),
        ]);
    }

    /**
     * @return array<int, SystemSettingHistory>
     */
    public function history(int $limit = 25): array
    {
        if (! $this->historyTableExists()) {
            return [];
        }

        return SystemSettingHistory::query()
            ->latest('changed_at')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function featureEnabled(string $key, bool $fallback = true): bool
    {
        return $this->boolean("feature_flags.{$key}", $fallback);
    }

    public function adminIpAllowed(?string $ip): bool
    {
        $allowed = $this->string('security.allowed_admin_ips');

        if ($allowed === '' || ! $ip) {
            return true;
        }

        return IpUtils::checkIp($ip, $this->listFromText($allowed));
    }

    /**
     * @return array<string, mixed>
     */
    private function storedValues(): array
    {
        if (! $this->tableExists()) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            return SystemSetting::query()
                ->get(['key', 'value'])
                ->mapWithKeys(fn (SystemSetting $setting): array => [$setting->key => $setting->value])
                ->all();
        });
    }

    private function tableExists(): bool
    {
        try {
            return Schema::hasTable('system_settings');
        } catch (\Throwable) {
            return false;
        }
    }

    private function historyTableExists(): bool
    {
        try {
            return Schema::hasTable('system_setting_histories');
        } catch (\Throwable) {
            return false;
        }
    }

    private function recordHistory(string $key, mixed $previousValue, mixed $newValue, ?Authenticatable $actor): void
    {
        if (! $this->historyTableExists()) {
            return;
        }

        SystemSettingHistory::query()->create([
            'setting_key'        => $key,
            'previous_value'     => $previousValue,
            'new_value'          => $newValue,
            'changed_by_user_id' => $actor?->getAuthIdentifier(),
            'changed_by_name'    => data_get($actor, 'name'),
            'changed_by_email'   => data_get($actor, 'email'),
            'changed_at'         => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function flattenGroupedPayload(array $payload): array
    {
        $flat = [];

        foreach ($payload as $group => $settings) {
            if (! is_array($settings)) {
                continue;
            }

            foreach ($settings as $name => $value) {
                $key = "{$group}.{$name}";

                if (array_key_exists($key, self::DEFINITIONS)) {
                    $flat[$key] = $value;
                }
            }
        }

        return $flat;
    }

    private function castValue(string $type, mixed $value): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            default   => is_string($value) ? trim($value) : $value,
        };
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

    private function groupLabel(string $group): string
    {
        return match ($group) {
            'general'        => 'General Settings',
            'authentication' => 'Authentication Settings',
            'security'       => 'Security Settings',
            'tenant_defaults' => 'Tenant Defaults',
            'feature_flags'  => 'Feature Flags',
            'email'          => 'Email Settings',
            'analytics'      => 'Analytics Settings',
            'storage'        => 'Storage Settings',
            'maintenance'    => 'Maintenance Settings',
            'seo'            => 'Public SEO Settings',
            'compliance'     => 'Compliance Settings',
            'social'         => 'Social Links',
            default          => str($group)->headline()->value(),
        };
    }
}
