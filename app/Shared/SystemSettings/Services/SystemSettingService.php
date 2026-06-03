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
     * @var array<string, array{group: string, label: string, type: string, default: mixed, public: bool, description?: string, options?: array<string, string>}>
     */
    private const DEFINITIONS = [
        'general.application_name'        => ['group' => 'general', 'label' => 'Application Name', 'type' => 'string', 'default' => 'Onlyvo', 'public' => true],
        'general.application_description' => ['group' => 'general', 'label' => 'Application Description', 'type' => 'text', 'default' => 'Onlyvo tenant platform', 'public' => true],
        'general.logo'                    => ['group' => 'general', 'label' => 'Logo', 'type' => 'image', 'default' => '', 'public' => true],
        'general.favicon'                 => ['group' => 'general', 'label' => 'Favicon', 'type' => 'image', 'default' => '', 'public' => true],
        'general.support_email'           => ['group' => 'general', 'label' => 'Support Email', 'type' => 'email', 'default' => '', 'public' => true],
        'general.support_phone'           => ['group' => 'general', 'label' => 'Support Phone', 'type' => 'string', 'default' => '', 'public' => true],
        'general.company_address'         => ['group' => 'general', 'label' => 'Company Address', 'type' => 'text', 'default' => '', 'public' => true],

        'authentication.allow_tenant_registration'  => ['group' => 'authentication', 'label' => 'Allow Tenant Registration', 'type' => 'boolean', 'default' => true, 'public' => true],
        'authentication.require_email_verification' => ['group' => 'authentication', 'label' => 'Require Email Verification', 'type' => 'boolean', 'default' => false, 'public' => false],

        'security.session_lifetime_minutes'        => ['group' => 'security', 'label' => 'Session Lifetime Minutes', 'type' => 'integer', 'default' => 120, 'public' => false],
        'security.login_rate_limit_attempts'       => ['group' => 'security', 'label' => 'Login Rate Limit Attempts', 'type' => 'integer', 'default' => 5, 'public' => false],
        'security.login_rate_limit_window_minutes' => ['group' => 'security', 'label' => 'Login Rate Limit Window Minutes', 'type' => 'integer', 'default' => 1, 'public' => false],
        'security.require_strong_passwords'        => ['group' => 'security', 'label' => 'Require Strong Passwords', 'type' => 'boolean', 'default' => false, 'public' => false],
        'security.minimum_password_length'         => ['group' => 'security', 'label' => 'Minimum Password Length', 'type' => 'integer', 'default' => 8, 'public' => false],
        'security.allowed_admin_ips'               => ['group' => 'security', 'label' => 'Allowed Admin IPs', 'type' => 'text', 'default' => '', 'public' => false],

        'tenant_defaults.default_tenant_timezone'      => ['group' => 'tenant_defaults', 'label' => 'Default Tenant Timezone', 'type' => 'string', 'default' => 'UTC', 'public' => false],
        'tenant_defaults.default_tenant_status'        => ['group' => 'tenant_defaults', 'label' => 'Default Tenant Status', 'type' => 'select', 'default' => 'active', 'public' => false, 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
        'tenant_defaults.default_tenant_trial_days'    => ['group' => 'tenant_defaults', 'label' => 'Default Tenant Trial Days', 'type' => 'integer', 'default' => 14, 'public' => false],
        'tenant_defaults.default_tenant_template_type' => ['group' => 'tenant_defaults', 'label' => 'Default Tenant Template Type', 'type' => 'string', 'default' => '', 'public' => false],
        'tenant_defaults.default_tenant_template_key'  => ['group' => 'tenant_defaults', 'label' => 'Default Tenant Template Key', 'type' => 'string', 'default' => '', 'public' => false],

        'feature_flags.enable_templates_module'       => ['group' => 'feature_flags', 'label' => 'Enable Templates Module', 'type' => 'boolean', 'default' => true, 'public' => true],
        'feature_flags.enable_posts_module'           => ['group' => 'feature_flags', 'label' => 'Enable Posts Module', 'type' => 'boolean', 'default' => true, 'public' => true],
        'feature_flags.enable_analytics_module'       => ['group' => 'feature_flags', 'label' => 'Enable Analytics Module', 'type' => 'boolean', 'default' => true, 'public' => true],
        'feature_flags.enable_design_requests_module' => ['group' => 'feature_flags', 'label' => 'Enable Design Requests Module', 'type' => 'boolean', 'default' => true, 'public' => true],
        'feature_flags.enable_cta_forms'              => ['group' => 'feature_flags', 'label' => 'Enable CTA Forms', 'type' => 'boolean', 'default' => true, 'public' => true],
        'feature_flags.enable_tracking_logs'          => ['group' => 'feature_flags', 'label' => 'Enable Tracking Logs', 'type' => 'boolean', 'default' => true, 'public' => true],

        'email.mail_driver'   => ['group' => 'email', 'label' => 'Mail Driver', 'type' => 'select', 'default' => 'smtp', 'public' => false, 'options' => ['smtp' => 'SMTP', 'sendmail' => 'Sendmail', 'mailgun' => 'Mailgun', 'ses' => 'Amazon SES', 'ses-v2' => 'Amazon SES v2', 'postmark' => 'Postmark', 'log' => 'Log', 'array' => 'Array']],
        'email.smtp_host'     => ['group' => 'email', 'label' => 'SMTP Host', 'type' => 'string', 'default' => '', 'public' => false],
        'email.smtp_port'     => ['group' => 'email', 'label' => 'SMTP Port', 'type' => 'integer', 'default' => 587, 'public' => false],
        'email.smtp_username' => ['group' => 'email', 'label' => 'SMTP Username', 'type' => 'string', 'default' => '', 'public' => false],
        'email.smtp_password' => ['group' => 'email', 'label' => 'SMTP Password', 'type' => 'password', 'default' => '', 'public' => false],
        'email.sender_name'   => ['group' => 'email', 'label' => 'Sender Name', 'type' => 'string', 'default' => 'Onlyvo', 'public' => false],
        'email.sender_email'  => ['group' => 'email', 'label' => 'Sender Email', 'type' => 'email', 'default' => 'hello@example.com', 'public' => false],

        'analytics.enable_visitor_tracking' => ['group' => 'analytics', 'label' => 'Enable Visitor Tracking', 'type' => 'boolean', 'default' => true, 'public' => false],
        'analytics.enable_cta_tracking'     => ['group' => 'analytics', 'label' => 'Enable CTA Tracking', 'type' => 'boolean', 'default' => true, 'public' => false],

        'storage.maximum_upload_size' => ['group' => 'storage', 'label' => 'Maximum Upload Size', 'type' => 'integer', 'default' => 4096, 'public' => false, 'description' => 'KB'],
        'storage.allowed_file_types'  => ['group' => 'storage', 'label' => 'Allowed File Types', 'type' => 'string', 'default' => 'jpg,jpeg,png,webp,gif', 'public' => false],

        'maintenance.maintenance_mode'           => ['group' => 'maintenance', 'label' => 'Maintenance Mode', 'type' => 'boolean', 'default' => false, 'public' => true],
        'maintenance.maintenance_message'        => ['group' => 'maintenance', 'label' => 'Maintenance Message', 'type' => 'text', 'default' => 'The platform is temporarily unavailable for maintenance.', 'public' => true],
        'maintenance.maintenance_start_time'     => ['group' => 'maintenance', 'label' => 'Maintenance Start Time', 'type' => 'string', 'default' => '', 'public' => true],
        'maintenance.maintenance_end_time'       => ['group' => 'maintenance', 'label' => 'Maintenance End Time', 'type' => 'string', 'default' => '', 'public' => true],
        'maintenance.allow_admin_bypass'         => ['group' => 'maintenance', 'label' => 'Allow Admin Bypass', 'type' => 'boolean', 'default' => true, 'public' => true],
        'maintenance.maintenance_affected_areas' => ['group' => 'maintenance', 'label' => 'Maintenance Affected Areas', 'type' => 'text', 'default' => '', 'public' => true],

        'seo.default_meta_title'           => ['group' => 'seo', 'label' => 'Default Meta Title', 'type' => 'string', 'default' => 'Onlyvo', 'public' => true],
        'seo.default_meta_description'     => ['group' => 'seo', 'label' => 'Default Meta Description', 'type' => 'text', 'default' => 'Onlyvo tenant platform', 'public' => true],
        'seo.open_graph_image'             => ['group' => 'seo', 'label' => 'Open Graph Image', 'type' => 'image', 'default' => '', 'public' => true],
        'seo.allow_search_engine_indexing' => ['group' => 'seo', 'label' => 'Allow Search Engine Indexing', 'type' => 'boolean', 'default' => true, 'public' => true],
        'seo.canonical_domain'             => ['group' => 'seo', 'label' => 'Canonical Domain', 'type' => 'string', 'default' => '', 'public' => true],

        'compliance.privacy_policy_url'    => ['group' => 'compliance', 'label' => 'Privacy Policy URL', 'type' => 'url', 'default' => '', 'public' => true],
        'compliance.terms_of_service_url'  => ['group' => 'compliance', 'label' => 'Terms of Service URL', 'type' => 'url', 'default' => '', 'public' => true],
        'compliance.cookie_notice_enabled' => ['group' => 'compliance', 'label' => 'Cookie Notice Enabled', 'type' => 'boolean', 'default' => false, 'public' => true],

        'social.facebook_url'  => ['group' => 'social', 'label' => 'Facebook URL', 'type' => 'url', 'default' => '', 'public' => true],
        'social.instagram_url' => ['group' => 'social', 'label' => 'Instagram URL', 'type' => 'url', 'default' => '', 'public' => true],
        'social.linkedin_url'  => ['group' => 'social', 'label' => 'LinkedIn URL', 'type' => 'url', 'default' => '', 'public' => true],
        'social.twitter_url'   => ['group' => 'social', 'label' => 'X/Twitter URL', 'type' => 'url', 'default' => '', 'public' => true],
        'social.youtube_url'   => ['group' => 'social', 'label' => 'YouTube URL', 'type' => 'url', 'default' => '', 'public' => true],
    ];

    private const ALIASES = [
        'platform_profile.application_name'                    => 'general.application_name',
        'platform_profile.application_description'             => 'general.application_description',
        'platform_profile.application_logo'                    => 'general.logo',
        'platform_profile.favicon'                             => 'general.favicon',
        'support_settings.support_email'                       => 'general.support_email',
        'support_settings.support_phone_number'                => 'general.support_phone',
        'tenant_management.tenant_registration_enabled'        => 'authentication.allow_tenant_registration',
        'tenant_management.trial_duration_days'                => 'tenant_defaults.default_tenant_trial_days',
        'user_access_control.email_verification_required'      => 'authentication.require_email_verification',
        'user_access_control.session_timeout_minutes'          => 'security.session_lifetime_minutes',
        'user_access_control.login_attempt_limit'              => 'security.login_rate_limit_attempts',
        'user_access_control.account_lockout_duration_minutes' => 'security.login_rate_limit_window_minutes',
        'user_access_control.require_strong_passwords'         => 'security.require_strong_passwords',
        'user_access_control.minimum_password_length'          => 'security.minimum_password_length',
        'security.ip_allow_list'                               => 'security.allowed_admin_ips',
        'platform_profile.default_timezone'                    => 'tenant_defaults.default_tenant_timezone',
        'tenant_management.default_tenant_status'              => 'tenant_defaults.default_tenant_status',
        'branding.default_tenant_template_type'                => 'tenant_defaults.default_tenant_template_type',
        'branding.default_tenant_template_key'                 => 'tenant_defaults.default_tenant_template_key',
        'feature_management.enable_templates_module'           => 'feature_flags.enable_templates_module',
        'feature_management.enable_posts_module'               => 'feature_flags.enable_posts_module',
        'feature_management.enable_analytics_module'           => 'feature_flags.enable_analytics_module',
        'feature_management.enable_design_requests_module'     => 'feature_flags.enable_design_requests_module',
        'feature_management.enable_cta_forms'                  => 'feature_flags.enable_cta_forms',
        'feature_management.enable_tracking_logs'              => 'feature_flags.enable_tracking_logs',
        'email_settings.smtp_provider'                         => 'email.mail_driver',
        'email_settings.smtp_host'                             => 'email.smtp_host',
        'email_settings.smtp_port'                             => 'email.smtp_port',
        'email_settings.smtp_username'                         => 'email.smtp_username',
        'email_settings.smtp_password'                         => 'email.smtp_password',
        'email_settings.sender_name'                           => 'email.sender_name',
        'email_settings.sender_email'                          => 'email.sender_email',
        'file_storage.maximum_upload_size'                     => 'storage.maximum_upload_size',
        'file_storage.allowed_file_types'                      => 'storage.allowed_file_types',
        'security.maintenance_mode'                            => 'maintenance.maintenance_mode',
        'security.maintenance_message'                         => 'maintenance.maintenance_message',
        'security.maintenance_start_time'                      => 'maintenance.maintenance_start_time',
        'security.maintenance_end_time'                        => 'maintenance.maintenance_end_time',
        'security.allow_admin_bypass'                          => 'maintenance.allow_admin_bypass',
        'security.maintenance_affected_areas'                  => 'maintenance.maintenance_affected_areas',
        'public_website_settings.public_registration_enabled'  => 'authentication.allow_tenant_registration',
        'public_website_settings.seo_meta_title'               => 'seo.default_meta_title',
        'public_website_settings.seo_meta_description'         => 'seo.default_meta_description',
        'public_website_settings.social_preview_image'         => 'seo.open_graph_image',
        'public_website_settings.allow_search_engine_indexing' => 'seo.allow_search_engine_indexing',
        'public_website_settings.canonical_domain'             => 'seo.canonical_domain',
        'legal_compliance.privacy_policy_url'                  => 'compliance.privacy_policy_url',
        'legal_compliance.terms_of_service_url'                => 'compliance.terms_of_service_url',
        'legal_compliance.cookie_notice_enabled'               => 'compliance.cookie_notice_enabled',
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
                'key'         => $key,
                'name'        => str($key)->after('.')->value(),
                'label'       => $definition['label'],
                'type'        => $definition['type'],
                'value'       => $values[$key] ?? $definition['default'],
                'is_public'   => $definition['public'],
                'description' => $definition['description'] ?? null,
                'options'     => $definition['options']     ?? null,
            ];
        }

        return array_values($groups);
    }

    /**
     * @return array<string, mixed>
     */
    public function values(bool $publicOnly = false): array
    {
        $stored = $this->storedValues();

        $values = collect(self::DEFINITIONS)
            ->when($publicOnly, fn ($definitions) => $definitions->filter(fn ($definition) => $definition['public']))
            ->mapWithKeys(function (array $definition, string $key) use ($stored): array {
                $aliasedValue = null;

                foreach (self::ALIASES as $alias => $canonicalKey) {
                    if ($canonicalKey === $key && array_key_exists($alias, $stored)) {
                        $aliasedValue = $stored[$alias];

                        break;
                    }
                }

                return [$key => $stored[$key] ?? $aliasedValue ?? $definition['default']];
            })
            ->all();

        foreach (self::ALIASES as $legacyKey => $key) {
            $definition = self::DEFINITIONS[$key] ?? null;

            if (! $definition || ($publicOnly && ! $definition['public'])) {
                continue;
            }

            $values[$legacyKey] = $values[$key] ?? $definition['default'];
        }

        return $values;
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        $key        = $this->canonicalKey($key);
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
        $key        = $this->canonicalKey($key);
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

    public function reconcileStoredSettings(): void
    {
        if (! $this->tableExists()) {
            return;
        }

        foreach (self::ALIASES as $alias => $canonicalKey) {
            $definition = self::DEFINITIONS[$canonicalKey] ?? null;
            $setting    = SystemSetting::query()->where('key', $alias)->first();

            if (! $setting) {
                continue;
            }

            if (! $definition) {
                $setting->delete();

                continue;
            }

            if (SystemSetting::query()->where('key', $canonicalKey)->exists()) {
                $setting->delete();

                continue;
            }

            $setting->update([
                'key'       => $canonicalKey,
                'group'     => $definition['group'],
                'label'     => $definition['label'],
                'type'      => $definition['type'],
                'is_public' => $definition['public'],
            ]);
        }

        SystemSetting::query()
            ->whereNotIn('key', array_keys(self::DEFINITIONS))
            ->delete();

        Cache::forget(self::CACHE_KEY);
    }

    public function applyRuntimeConfig(): void
    {
        if (! $this->tableExists()) {
            return;
        }

        config([
            'app.name'                   => $this->string('general.application_name', (string) config('app.name')),
            'mail.default'               => $this->string('email.mail_driver', (string) config('mail.default')),
            'mail.from.name'             => $this->string('email.sender_name', (string) config('mail.from.name')),
            'mail.from.address'          => $this->string('email.sender_email', (string) config('mail.from.address')),
            'mail.mailers.smtp.host'     => $this->string('email.smtp_host', (string) config('mail.mailers.smtp.host')),
            'mail.mailers.smtp.port'     => $this->integer('email.smtp_port', (int) config('mail.mailers.smtp.port')),
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

    public function maintenanceActive(): bool
    {
        if (! $this->boolean('maintenance.maintenance_mode')) {
            return false;
        }

        $now      = now();
        $startsAt = $this->string('maintenance.maintenance_start_time');
        $endsAt   = $this->string('maintenance.maintenance_end_time');

        return ($startsAt === '' || $now->greaterThanOrEqualTo($startsAt))
            && ($endsAt === '' || $now->lessThanOrEqualTo($endsAt));
    }

    public function adminBypassesMaintenance(): bool
    {
        return $this->boolean('maintenance.allow_admin_bypass', true);
    }

    public function maintenanceAffectsPath(string $path): bool
    {
        $areas = $this->listFromText($this->string('maintenance.maintenance_affected_areas'));

        if ($areas === []) {
            return true;
        }

        $path = '/'.trim($path, '/');

        return collect($areas)->contains(function (string $area) use ($path): bool {
            $area = '/'.trim($area, '/');

            if ($area === '/') {
                return true;
            }

            return str_starts_with($path, $area)
                || str_starts_with($path, '/api'.$area)
                || str_starts_with($path, '/api/public'.$area)
                || str_starts_with($path, '/api/app'.$area);
        });
    }

    private function canonicalKey(string $key): string
    {
        return self::ALIASES[$key] ?? $key;
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
            'general'         => 'General Settings',
            'authentication'  => 'Authentication Settings',
            'security'        => 'Security Settings',
            'tenant_defaults' => 'Tenant Defaults',
            'feature_flags'   => 'Feature Flags',
            'email'           => 'Email Settings',
            'analytics'       => 'Analytics Settings',
            'storage'         => 'Storage Settings',
            'maintenance'     => 'Maintenance Settings',
            'seo'             => 'Public SEO Settings',
            'compliance'      => 'Compliance Settings',
            'social'          => 'Social Links',
            default           => str($group)->headline()->value(),
        };
    }
}
