<?php

namespace App\Admin\SystemSettings\Services;

use App\Admin\AuditLogs\Services\AuditLogService;
use App\Admin\SystemSettings\Models\SystemSetting;
use App\Admin\SystemSettings\Models\SystemSettingHistory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Mail\Message;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\IpUtils;

class SystemSettingService
{
    public const CACHE_KEY = 'system_settings.values';

    public const MASK_VALUE = '********';

    /**
     * @var array<string, array{group: string, label: string, type: string, default: mixed, public: bool, description?: string, options?: array<string, string>}>
     */
    private const DEFINITIONS = [
        'general.application_name'        => ['group' => 'general', 'label' => 'Application Name', 'type' => 'string', 'default' => 'Onlyvo', 'public' => true, 'description' => 'Enter the platform name shown in pages and metadata.'],
        'general.application_description' => ['group' => 'general', 'label' => 'Application Description', 'type' => 'text', 'default' => 'Onlyvo tenant platform', 'public' => true, 'description' => 'Enter a short summary used in public page metadata.'],
        'general.logo'                    => ['group' => 'general', 'label' => 'Logo', 'type' => 'image', 'default' => '', 'public' => true, 'description' => 'Enter or upload the logo image shown in the app.'],
        'general.favicon'                 => ['group' => 'general', 'label' => 'Favicon', 'type' => 'image', 'default' => '', 'public' => true, 'description' => 'Enter or upload the small browser tab icon.'],
        'general.support_email'           => ['group' => 'general', 'label' => 'Support Email', 'type' => 'email', 'default' => '', 'public' => true, 'description' => 'Enter the support email shown to users.'],
        'general.support_phone'           => ['group' => 'general', 'label' => 'Support Phone', 'type' => 'string', 'default' => '', 'public' => true, 'description' => 'Enter the support phone number shown to users.'],
        'general.company_address'         => ['group' => 'general', 'label' => 'Company Address', 'type' => 'text', 'default' => '', 'public' => true, 'description' => 'Enter the company address shown in public areas.'],
        'general.show_field_descriptions' => ['group' => 'general', 'label' => 'Show Field Descriptions', 'type' => 'boolean', 'default' => true, 'public' => true, 'description' => 'Turn field hover help text on or off.'],

        'authentication.allow_tenant_registration'  => ['group' => 'authentication', 'label' => 'Allow Tenant Registration', 'type' => 'boolean', 'default' => true, 'public' => true, 'description' => 'Allow new tenants to create accounts from registration.'],
        'authentication.require_email_verification' => ['group' => 'authentication', 'label' => 'Require Email Verification', 'type' => 'boolean', 'default' => false, 'public' => false, 'description' => 'Require users to verify email before tenant login.'],

        'security.session_lifetime_minutes'        => ['group' => 'security', 'label' => 'Session Lifetime Minutes', 'type' => 'integer', 'default' => 120, 'public' => false, 'description' => 'Enter how many minutes a login session remains active.'],
        'security.login_rate_limit_attempts'       => ['group' => 'security', 'label' => 'Login Rate Limit Attempts', 'type' => 'integer', 'default' => 5, 'public' => false, 'description' => 'Enter the allowed login attempts before rate limiting.'],
        'security.login_rate_limit_window_minutes' => ['group' => 'security', 'label' => 'Login Rate Limit Window Minutes', 'type' => 'integer', 'default' => 1, 'public' => false, 'description' => 'Enter the minutes used for login rate limiting.'],
        'security.require_strong_passwords'        => ['group' => 'security', 'label' => 'Require Strong Passwords', 'type' => 'boolean', 'default' => false, 'public' => false, 'description' => 'Require stronger password rules for account passwords.'],
        'security.minimum_password_length'         => ['group' => 'security', 'label' => 'Minimum Password Length', 'type' => 'integer', 'default' => 8, 'public' => false, 'description' => 'Enter the minimum number of password characters.'],
        'security.allowed_admin_ips'               => ['group' => 'security', 'label' => 'Allowed Admin IPs', 'type' => 'text', 'default' => '', 'public' => false, 'description' => 'Enter admin IP addresses or CIDR ranges separated by commas or lines.'],

        'tenant_defaults.default_tenant_timezone'      => ['group' => 'tenant_defaults', 'label' => 'Default Tenant Timezone', 'type' => 'string', 'default' => 'UTC', 'public' => false, 'description' => 'Enter the timezone assigned to new tenants.'],
        'tenant_defaults.default_tenant_status'        => ['group' => 'tenant_defaults', 'label' => 'Default Tenant Status', 'type' => 'select', 'default' => 'active', 'public' => false, 'description' => 'Choose the status assigned to new tenants.', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
        'tenant_defaults.default_tenant_trial_days'    => ['group' => 'tenant_defaults', 'label' => 'Default Tenant Trial Days', 'type' => 'integer', 'default' => 14, 'public' => false, 'description' => 'Enter the number of trial days for new tenants.'],
        'tenant_defaults.default_tenant_template_type' => ['group' => 'tenant_defaults', 'label' => 'Default Tenant Template Type', 'type' => 'string', 'default' => '', 'public' => false, 'description' => 'Enter the default website type slug for new tenants.'],
        'tenant_defaults.default_tenant_template_key'  => ['group' => 'tenant_defaults', 'label' => 'Default Tenant Template Key', 'type' => 'string', 'default' => '', 'public' => false, 'description' => 'Enter the default template key for new tenants.'],

        'feature_flags.enable_templates_module'       => ['group' => 'feature_flags', 'label' => 'Enable Templates Module', 'type' => 'boolean', 'default' => true, 'public' => true, 'description' => 'Show or hide template management features.'],
        'feature_flags.enable_posts_module'           => ['group' => 'feature_flags', 'label' => 'Enable Posts Module', 'type' => 'boolean', 'default' => true, 'public' => true, 'description' => 'Show or hide post management features.'],
        'feature_flags.enable_analytics_module'       => ['group' => 'feature_flags', 'label' => 'Enable Analytics Module', 'type' => 'boolean', 'default' => true, 'public' => true, 'description' => 'Show or hide analytics features.'],
        'feature_flags.enable_design_requests_module' => ['group' => 'feature_flags', 'label' => 'Enable Design Requests Module', 'type' => 'boolean', 'default' => true, 'public' => true, 'description' => 'Show or hide design request features.'],
        'feature_flags.enable_cta_forms'              => ['group' => 'feature_flags', 'label' => 'Enable CTA Forms', 'type' => 'boolean', 'default' => true, 'public' => true, 'description' => 'Allow public CTA forms to collect submissions.'],
        'feature_flags.enable_tracking_logs'          => ['group' => 'feature_flags', 'label' => 'Enable Tracking Logs', 'type' => 'boolean', 'default' => true, 'public' => true, 'description' => 'Show or hide raw tracking log features.'],
        'feature_flags.controlled_rollout_percentage' => ['group' => 'feature_flags', 'label' => 'Controlled Rollout Percentage', 'type' => 'integer', 'default' => 100, 'public' => true, 'description' => 'Limit feature availability to a percentage of tenants when rollout logic is enabled.'],

        'email.mail_driver'   => ['group' => 'email', 'label' => 'Mail Driver', 'type' => 'select', 'default' => 'smtp', 'public' => false, 'description' => 'Choose the mail service used to send platform email.', 'options' => ['smtp' => 'SMTP', 'sendmail' => 'Sendmail', 'mailgun' => 'Mailgun', 'ses' => 'Amazon SES', 'ses-v2' => 'Amazon SES v2', 'postmark' => 'Postmark', 'log' => 'Log', 'array' => 'Array']],
        'email.smtp_host'     => ['group' => 'email', 'label' => 'SMTP Host', 'type' => 'string', 'default' => '', 'public' => false, 'description' => 'Enter the SMTP server host name.'],
        'email.smtp_port'     => ['group' => 'email', 'label' => 'SMTP Port', 'type' => 'integer', 'default' => 587, 'public' => false, 'description' => 'Enter the SMTP server port number.'],
        'email.smtp_username' => ['group' => 'email', 'label' => 'SMTP Username', 'type' => 'string', 'default' => '', 'public' => false, 'description' => 'Enter the SMTP username if required.'],
        'email.smtp_password' => ['group' => 'email', 'label' => 'SMTP Password', 'type' => 'password', 'default' => '', 'public' => false, 'description' => 'Enter the SMTP password if required.'],
        'email.sender_name'   => ['group' => 'email', 'label' => 'Sender Name', 'type' => 'string', 'default' => 'Onlyvo', 'public' => false, 'description' => 'Enter the name shown as the email sender.'],
        'email.sender_email'  => ['group' => 'email', 'label' => 'Sender Email', 'type' => 'email', 'default' => 'hello@example.com', 'public' => false, 'description' => 'Enter the email address used as the sender.'],

        'analytics.enable_visitor_tracking' => ['group' => 'analytics', 'label' => 'Enable Visitor Tracking', 'type' => 'boolean', 'default' => true, 'public' => false, 'description' => 'Record public website visits for analytics.'],
        'analytics.enable_cta_tracking'     => ['group' => 'analytics', 'label' => 'Enable CTA Tracking', 'type' => 'boolean', 'default' => true, 'public' => false, 'description' => 'Record CTA views, clicks, and submissions.'],

        'storage.maximum_upload_size' => ['group' => 'storage', 'label' => 'Maximum Upload Size', 'type' => 'integer', 'default' => 4096, 'public' => false, 'description' => 'Enter the maximum upload size in KB.'],
        'storage.allowed_file_types'  => ['group' => 'storage', 'label' => 'Allowed File Types', 'type' => 'string', 'default' => 'jpg,jpeg,png,webp,gif', 'public' => false, 'description' => 'Enter allowed file extensions separated by commas.'],

        'maintenance.maintenance_mode'           => ['group' => 'maintenance', 'label' => 'Maintenance Mode', 'type' => 'boolean', 'default' => false, 'public' => true, 'description' => 'Temporarily block affected users from the platform.'],
        'maintenance.maintenance_message'        => ['group' => 'maintenance', 'label' => 'Maintenance Message', 'type' => 'text', 'default' => 'The platform is temporarily unavailable for maintenance.', 'public' => true, 'description' => 'Enter the message shown during maintenance.'],
        'maintenance.maintenance_start_time'     => ['group' => 'maintenance', 'label' => 'Maintenance Start Time', 'type' => 'string', 'default' => '', 'public' => true, 'description' => 'Enter when maintenance should start.'],
        'maintenance.maintenance_end_time'       => ['group' => 'maintenance', 'label' => 'Maintenance End Time', 'type' => 'string', 'default' => '', 'public' => true, 'description' => 'Enter when maintenance should end.'],
        'maintenance.allow_admin_bypass'         => ['group' => 'maintenance', 'label' => 'Allow Admin Bypass', 'type' => 'boolean', 'default' => true, 'public' => true, 'description' => 'Allow admins to keep using the platform during maintenance.'],
        'maintenance.maintenance_affected_areas' => ['group' => 'maintenance', 'label' => 'Maintenance Affected Areas', 'type' => 'text', 'default' => '', 'public' => true, 'description' => 'Enter paths affected by maintenance, one per line or comma.'],

        'seo.default_meta_title'           => ['group' => 'seo', 'label' => 'Default Meta Title', 'type' => 'string', 'default' => 'Onlyvo', 'public' => true, 'description' => 'Enter the default browser and social title.'],
        'seo.default_meta_description'     => ['group' => 'seo', 'label' => 'Default Meta Description', 'type' => 'text', 'default' => 'Onlyvo tenant platform', 'public' => true, 'description' => 'Enter the default search and social description.'],
        'seo.open_graph_image'             => ['group' => 'seo', 'label' => 'Open Graph Image', 'type' => 'image', 'default' => '', 'public' => true, 'description' => 'Enter or upload the image used for social previews.'],
        'seo.allow_search_engine_indexing' => ['group' => 'seo', 'label' => 'Allow Search Engine Indexing', 'type' => 'boolean', 'default' => true, 'public' => true, 'description' => 'Allow search engines to index public pages.'],
        'seo.canonical_domain'             => ['group' => 'seo', 'label' => 'Canonical Domain', 'type' => 'string', 'default' => '', 'public' => true, 'description' => 'Enter the preferred public domain for canonical URLs.'],

        'compliance.privacy_policy_url'    => ['group' => 'compliance', 'label' => 'Privacy Policy URL', 'type' => 'url', 'default' => '', 'public' => true, 'description' => 'Enter the public URL for the privacy policy.'],
        'compliance.terms_of_service_url'  => ['group' => 'compliance', 'label' => 'Terms of Service URL', 'type' => 'url', 'default' => '', 'public' => true, 'description' => 'Enter the public URL for the terms of service.'],
        'compliance.cookie_notice_enabled' => ['group' => 'compliance', 'label' => 'Cookie Notice Enabled', 'type' => 'boolean', 'default' => false, 'public' => true, 'description' => 'Show the cookie notice on public pages.'],

        'social.facebook_url'  => ['group' => 'social', 'label' => 'Facebook URL', 'type' => 'url', 'default' => '', 'public' => true, 'description' => 'Enter the Facebook profile or page URL.'],
        'social.instagram_url' => ['group' => 'social', 'label' => 'Instagram URL', 'type' => 'url', 'default' => '', 'public' => true, 'description' => 'Enter the Instagram profile URL.'],
        'social.linkedin_url'  => ['group' => 'social', 'label' => 'LinkedIn URL', 'type' => 'url', 'default' => '', 'public' => true, 'description' => 'Enter the LinkedIn profile or page URL.'],
        'social.twitter_url'   => ['group' => 'social', 'label' => 'X/Twitter URL', 'type' => 'url', 'default' => '', 'public' => true, 'description' => 'Enter the X or Twitter profile URL.'],
        'social.youtube_url'   => ['group' => 'social', 'label' => 'YouTube URL', 'type' => 'url', 'default' => '', 'public' => true, 'description' => 'Enter the YouTube channel URL.'],
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
        'feature_management.controlled_rollout_percentage'     => 'feature_flags.controlled_rollout_percentage',
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
    public function groups(bool $publicOnly = false, bool $maskSensitive = false): array
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
                'key'          => $key,
                'name'         => str($key)->after('.')->value(),
                'label'        => $definition['label'],
                'type'         => $definition['type'],
                'value'        => $this->safeValue($key, $values[$key] ?? $definition['default'], $maskSensitive),
                'is_public'    => $definition['public'],
                'is_sensitive' => $this->isSensitiveKey($key, $definition),
                'is_masked'    => $maskSensitive && $this->isSensitiveKey($key, $definition) && $this->string($key) !== '',
                'description'  => $definition['description'] ?? null,
                'options'      => $definition['options']     ?? null,
            ];
        }

        return array_values($groups);
    }

    /**
     * @return array<string, mixed>
     */
    public function values(bool $publicOnly = false, bool $maskSensitive = false): array
    {
        $stored = $this->storedValues();

        $values = collect(self::DEFINITIONS)
            ->when($publicOnly, fn ($definitions) => $definitions->filter(fn ($definition) => $definition['public']))
            ->mapWithKeys(function (array $definition, string $key) use ($stored, $maskSensitive): array {
                $aliasedValue = null;

                foreach (self::ALIASES as $alias => $canonicalKey) {
                    if ($canonicalKey === $key && array_key_exists($alias, $stored)) {
                        $aliasedValue = $stored[$alias];

                        break;
                    }
                }

                $value = $this->castValue(
                    (string) $definition['type'],
                    $stored[$key] ?? $aliasedValue ?? $definition['default'],
                );

                return [$key => $this->safeValue($key, $value, $maskSensitive)];
            })
            ->all();

        foreach (self::ALIASES as $legacyKey => $key) {
            $definition = self::DEFINITIONS[$key] ?? null;

            if (! $definition || ($publicOnly && ! $definition['public'])) {
                continue;
            }

            $values[$legacyKey] = $this->safeValue(
                $key,
                $values[$key] ?? $this->castValue((string) $definition['type'], $definition['default']),
                $maskSensitive,
            );
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
        $value = $this->get($key, $fallback);

        return $value === null || $value === '' ? $fallback : (int) $value;
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
        $saved = DB::transaction(function () use ($payload, $actor): array {
            $saved = [];

            foreach ($this->flattenGroupedPayload($payload) as $key => $value) {
                $saved[] = $this->upsert($key, $value, $actor);
            }

            return $saved;
        });

        Cache::forget(self::CACHE_KEY);

        return $saved;
    }

    public function upsert(string $key, mixed $value, ?Authenticatable $actor = null): SystemSetting
    {
        $key        = $this->canonicalKey($key);
        $definition = self::DEFINITIONS[$key];
        $existing   = SystemSetting::query()->where('key', $key)->first();
        $newValue   = $this->isMaskedPlaceholder($key, $value)
            ? ($existing?->value ?? $definition['default'])
            : $this->castValue($definition['type'], $value);
        $previousValue = $existing?->value ?? $definition['default'];

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

        if (! $this->valuesAreEqual($definition['type'], $previousValue, $newValue)) {
            $this->recordHistory($key, $existing?->value, $newValue, $actor, $existing ? 'updated' : 'created');
        }

        Cache::forget(self::CACHE_KEY);

        return $setting;
    }

    public function delete(SystemSetting $setting, ?Authenticatable $actor = null): void
    {
        $this->recordHistory($setting->key, $setting->value, null, $actor, 'deleted');
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
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function history(array $filters = []): array
    {
        if (! $this->historyTableExists()) {
            return [
                'data'       => [],
                'pagination' => $this->emptyPagination(),
            ];
        }

        $query = SystemSettingHistory::query();

        $this->applyHistoryFilters($query, $filters);
        $this->applyHistorySorting($query, $filters);

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate(
            (int) ($filters['pageSize'] ?? 15),
            ['*'],
            'page',
            (int) ($filters['page'] ?? 1),
        );

        return [
            'data'       => $paginator->getCollection(),
            'pagination' => $this->pagination($paginator),
        ];
    }

    public function definitionExists(string $key): bool
    {
        return array_key_exists($this->canonicalKey($key), self::DEFINITIONS);
    }

    public function displayValue(string $key, mixed $value, bool $maskSensitive = true): mixed
    {
        return $this->safeValue($this->canonicalKey($key), $value, $maskSensitive);
    }

    /**
     * @return array<string, mixed>
     */
    public function exportPayload(?Authenticatable $actor = null, string $type = 'export'): array
    {
        $payload = [
            'type'             => $type,
            'generated_at'     => now()->toISOString(),
            'masked_sensitive' => true,
            'groups'           => $this->groups(maskSensitive: true),
            'values'           => $this->values(maskSensitive: true),
        ];

        $this->recordHistory(
            'system_settings.'.$type,
            null,
            ['generated_at' => $payload['generated_at'], 'masked_sensitive' => true],
            $actor,
            $type === 'backup' ? 'backed_up' : 'exported',
        );

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function testSmtpConnection(): array
    {
        $this->applyRuntimeConfig();

        $driver = $this->string('email.mail_driver', 'smtp');

        if ($driver !== 'smtp') {
            return [
                'status'  => 'skipped',
                'message' => 'SMTP connection test only applies when the SMTP mail driver is selected.',
                'driver'  => $driver,
            ];
        }

        $host = $this->string('email.smtp_host');
        $port = $this->integer('email.smtp_port', 587);

        if ($host === '') {
            return [
                'status'  => 'failed',
                'message' => 'SMTP host is required before testing the connection.',
                'driver'  => $driver,
            ];
        }

        $startedAt = microtime(true);
        $socket    = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errorCode,
            $errorMessage,
            8,
            STREAM_CLIENT_CONNECT,
        );

        if (! $socket) {
            return [
                'status'  => 'failed',
                'message' => trim($errorMessage) !== '' ? $errorMessage : 'Unable to connect to the SMTP server.',
                'driver'  => $driver,
                'host'    => $host,
                'port'    => $port,
                'code'    => $errorCode,
            ];
        }

        fclose($socket);

        return [
            'status'     => 'ok',
            'message'    => 'SMTP server accepted a TCP connection.',
            'driver'     => $driver,
            'host'       => $host,
            'port'       => $port,
            'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sendTestEmail(string $recipient, ?Authenticatable $actor = null): array
    {
        $this->applyRuntimeConfig();
        app('mail.manager')->forgetMailers();

        Mail::raw(
            'This is a test email from the Onlyvo admin System Settings console.',
            function (Message $message) use ($recipient): void {
                $message
                    ->to($recipient)
                    ->subject('Onlyvo test email');
            },
        );

        $this->recordHistory('email.test_delivery', null, ['recipient' => $recipient], $actor, 'tested');

        return [
            'status'    => 'sent',
            'recipient' => $recipient,
            'driver'    => $this->string('email.mail_driver', 'smtp'),
            'message'   => 'Test email handed to the configured mail transport.',
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function maintenancePreview(array $settings = [], string $path = '/'): array
    {
        $values  = array_replace($this->values(), $this->flattenGroupedPayload($settings));
        $enabled = filter_var($values['maintenance.maintenance_mode'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $areas   = $this->listFromText((string) ($values['maintenance.maintenance_affected_areas'] ?? ''));
        $path    = '/'.trim($path, '/');
        $path    = $path === '/' ? '/' : $path;

        $startsAt = trim((string) ($values['maintenance.maintenance_start_time'] ?? ''));
        $endsAt   = trim((string) ($values['maintenance.maintenance_end_time'] ?? ''));
        $now      = now();
        $active   = $enabled
            && ($startsAt === '' || $now->greaterThanOrEqualTo($startsAt))
            && ($endsAt === '' || $now->lessThanOrEqualTo($endsAt));

        $pathAffected = $this->pathAffectedByAreas($path, $areas);

        return [
            'dry_run'          => true,
            'enabled'          => $enabled,
            'active_now'       => $active,
            'path'             => $path,
            'path_affected'    => $pathAffected,
            'admin_bypass'     => filter_var($values['maintenance.allow_admin_bypass'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'message'          => $values['maintenance.maintenance_message'] ?? '',
            'starts_at'        => $startsAt,
            'ends_at'          => $endsAt,
            'affected_areas'   => $areas,
            'affected_summary' => $areas === [] ? 'All platform areas' : implode(', ', $areas),
            'result'           => $active && $pathAffected ? 'blocked' : 'allowed',
        ];
    }

    public function restoreHistory(SystemSettingHistory $history, ?Authenticatable $actor = null): ?SystemSetting
    {
        $key = $this->canonicalKey((string) $history->setting_key);

        if (! $this->definitionExists($key)) {
            return null;
        }

        $definition = self::DEFINITIONS[$key];
        $existing   = SystemSetting::query()->where('key', $key)->first();

        if ($history->previous_value === null) {
            if ($existing) {
                $this->recordHistory($key, $existing->value, null, $actor, 'restored');
                $existing->delete();
                Cache::forget(self::CACHE_KEY);
            }

            return null;
        }

        $newValue = $this->castValue($definition['type'], $history->previous_value);

        $restored = SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'group'     => $definition['group'],
                'label'     => $definition['label'],
                'type'      => $definition['type'],
                'value'     => $newValue,
                'is_public' => $definition['public'],
            ],
        );

        $this->recordHistory($key, $existing?->value, $history->previous_value, $actor, 'restored');
        Cache::forget(self::CACHE_KEY);

        return $restored;
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

        return $this->pathAffectedByAreas($path, $areas);
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

    private function recordHistory(string $key, mixed $previousValue, mixed $newValue, ?Authenticatable $actor, string $action): void
    {
        if (! $this->historyTableExists()) {
            return;
        }

        SystemSettingHistory::query()->create([
            'setting_key'        => $key,
            'action'             => $action,
            'previous_value'     => $previousValue,
            'new_value'          => $newValue,
            'changed_by_user_id' => $actor?->getAuthIdentifier(),
            'changed_by_name'    => data_get($actor, 'name'),
            'changed_by_email'   => data_get($actor, 'email'),
            'changed_at'         => now(),
        ]);

        app(AuditLogService::class)->record([
            'entity_type'    => 'system_setting',
            'entity_id'      => $key,
            'entity_label'   => $key,
            'action'         => 'system_setting.'.$action,
            'previous_value' => $previousValue,
            'new_value'      => $newValue,
        ], $actor, request());
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function isSensitiveKey(string $key, array $definition): bool
    {
        return $definition['type'] === 'password'
            || Str::contains($key, ['password', 'secret', 'token', 'api_key', 'private_key']);
    }

    private function isMaskedPlaceholder(string $key, mixed $value): bool
    {
        $definition = self::DEFINITIONS[$key] ?? null;

        return is_string($value)
            && $definition
            && $this->isSensitiveKey($key, $definition)
            && $value === self::MASK_VALUE;
    }

    private function safeValue(string $key, mixed $value, bool $maskSensitive): mixed
    {
        $definition = self::DEFINITIONS[$key] ?? null;

        if (! $maskSensitive || ! $definition || ! $this->isSensitiveKey($key, $definition)) {
            return $value;
        }

        return trim((string) $value) === '' ? '' : self::MASK_VALUE;
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
            'integer' => $value === null || $value === '' ? null : (int) $value,
            default   => is_string($value) ? trim($value) : $value,
        };
    }

    private function valuesAreEqual(string $type, mixed $previousValue, mixed $newValue): bool
    {
        if (! in_array($type, ['boolean', 'integer'], true)) {
            $previousValue ??= '';
            $newValue ??= '';
        }

        return $previousValue === $newValue;
    }

    /**
     * @param  Builder<SystemSettingHistory>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyHistoryFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['setting_key'] ?? null, fn (Builder $query, string $key) => $query->where('setting_key', $key))
            ->when($filters['action'] ?? null, fn (Builder $query, string $action) => $query->where('action', $action))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $like = '%'.Str::lower($search).'%';

                $query->where(function (Builder $query) use ($like): void {
                    foreach (['setting_key', 'action', 'changed_by_name', 'changed_by_email'] as $column) {
                        $query->orWhereRaw('lower(coalesce('.$column.", '')) like ?", [$like]);
                    }
                });
            });
    }

    /**
     * @param  Builder<SystemSettingHistory>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyHistorySorting(Builder $query, array $filters): void
    {
        $sortMap = [
            'setting_key' => 'setting_key',
            'action'      => 'action',
            'changed_by'  => 'changed_by_name',
            'changed_at'  => 'changed_at',
        ];

        $sort      = $sortMap[(string) ($filters['sort'] ?? 'changed_at')] ?? 'changed_at';
        $direction = (string) ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sort, $direction)->orderBy('id', 'desc');
    }

    /**
     * @return array<string, int|null>
     */
    private function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'from'         => $paginator->firstItem(),
            'to'           => $paginator->lastItem(),
        ];
    }

    /**
     * @return array<string, int|null>
     */
    private function emptyPagination(): array
    {
        return [
            'total'        => 0,
            'per_page'     => 15,
            'current_page' => 1,
            'last_page'    => 1,
            'from'         => null,
            'to'           => null,
        ];
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

    /**
     * @param  array<int, string>  $areas
     */
    private function pathAffectedByAreas(string $path, array $areas): bool
    {
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
