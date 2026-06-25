<?php

namespace App\Tenant\SystemSettings\Services;

use App\Tenant\SystemSettings\Models\TenantSystemSetting;
use App\Tenant\SystemSettings\Models\TenantSystemSettingHistory;
use App\Tenant\Tenants\Models\Tenant;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TenantSystemSettingService
{
    /**
     * @var array<string, array{group: string, label: string, type: string, default: mixed, public: bool, description?: string, options?: array<string, string>}>
     */
    private const DEFINITIONS = [
        'profile.business_name'   => ['group' => 'profile', 'label' => 'Business Name', 'type' => 'string', 'default' => '', 'public' => true, 'description' => 'Enter the business or public site name.'],
        'profile.description'     => ['group' => 'profile', 'label' => 'Description', 'type' => 'text', 'default' => '', 'public' => true, 'description' => 'Enter a short description for public profile and metadata defaults.'],
        'profile.logo'            => ['group' => 'profile', 'label' => 'Logo', 'type' => 'image', 'default' => '', 'public' => true, 'description' => 'Upload the logo used on the tenant public site.'],
        'profile.favicon'         => ['group' => 'profile', 'label' => 'Favicon', 'type' => 'image', 'default' => '', 'public' => true, 'description' => 'Upload the browser tab icon for the tenant public site.'],
        'profile.timezone'        => ['group' => 'profile', 'label' => 'Timezone', 'type' => 'string', 'default' => 'UTC', 'public' => false, 'description' => 'Enter the timezone used for tenant reports and dates.'],
        'profile.contact_email'   => ['group' => 'profile', 'label' => 'Contact Email', 'type' => 'email', 'default' => '', 'public' => true, 'description' => 'Enter the public contact email address.'],
        'profile.contact_phone'   => ['group' => 'profile', 'label' => 'Contact Phone', 'type' => 'string', 'default' => '', 'public' => true, 'description' => 'Enter the public contact phone number.'],
        'profile.contact_address' => ['group' => 'profile', 'label' => 'Contact Address', 'type' => 'text', 'default' => '', 'public' => true, 'description' => 'Enter the public business address.'],

        'website.site_status'          => ['group' => 'website', 'label' => 'Site Status', 'type' => 'select', 'default' => 'live', 'public' => true, 'description' => 'Choose whether the public website should be treated as draft or live.', 'options' => ['draft' => 'Draft', 'live' => 'Live']],
        'website.homepage_slug'        => ['group' => 'website', 'label' => 'Homepage Slug', 'type' => 'string', 'default' => '', 'public' => true, 'description' => 'Enter the default public site slug, or leave blank to use the default template.'],
        'website.primary_cta_label'    => ['group' => 'website', 'label' => 'Primary CTA Label', 'type' => 'string', 'default' => '', 'public' => true, 'description' => 'Enter the default call-to-action button label.'],
        'website.primary_cta_url'      => ['group' => 'website', 'label' => 'Primary CTA URL', 'type' => 'url', 'default' => '', 'public' => true, 'description' => 'Enter the default call-to-action destination URL.'],
        'website.contact_form_enabled' => ['group' => 'website', 'label' => 'Contact Form Enabled', 'type' => 'boolean', 'default' => true, 'public' => true, 'description' => 'Allow the public site to show tenant contact forms where templates support them.'],
        'website.footer_text'          => ['group' => 'website', 'label' => 'Footer Text', 'type' => 'text', 'default' => '', 'public' => true, 'description' => 'Enter footer or copyright text for public pages.'],

        'seo.default_meta_title'           => ['group' => 'seo', 'label' => 'Default Meta Title', 'type' => 'string', 'default' => '', 'public' => true, 'description' => 'Enter the default browser and search title for tenant public pages.'],
        'seo.default_meta_description'     => ['group' => 'seo', 'label' => 'Default Meta Description', 'type' => 'text', 'default' => '', 'public' => true, 'description' => 'Enter the default search and social description for tenant public pages.'],
        'seo.open_graph_image'             => ['group' => 'seo', 'label' => 'Open Graph Image', 'type' => 'image', 'default' => '', 'public' => true, 'description' => 'Upload the default image used when tenant public links are shared.'],
        'seo.allow_search_engine_indexing' => ['group' => 'seo', 'label' => 'Allow Search Engine Indexing', 'type' => 'boolean', 'default' => true, 'public' => true, 'description' => 'Allow search engines to index tenant public pages.'],
        'seo.canonical_domain'             => ['group' => 'seo', 'label' => 'Canonical Domain', 'type' => 'string', 'default' => '', 'public' => true, 'description' => 'Enter the preferred tenant public domain for canonical URLs.'],

        'branding.primary_color'  => ['group' => 'branding', 'label' => 'Primary Color', 'type' => 'color', 'default' => '#2563eb', 'public' => true, 'description' => 'Choose the primary brand color.'],
        'branding.accent_color'   => ['group' => 'branding', 'label' => 'Accent Color', 'type' => 'color', 'default' => '#10b981', 'public' => true, 'description' => 'Choose the accent brand color.'],
        'branding.font_family'    => ['group' => 'branding', 'label' => 'Font Family', 'type' => 'select', 'default' => 'system', 'public' => true, 'description' => 'Choose the default public site font style.', 'options' => ['system' => 'System', 'inter' => 'Inter', 'serif' => 'Serif', 'mono' => 'Mono']],
        'branding.button_radius'  => ['group' => 'branding', 'label' => 'Button Radius', 'type' => 'select', 'default' => 'medium', 'public' => true, 'description' => 'Choose the default button corner style.', 'options' => ['none' => 'None', 'small' => 'Small', 'medium' => 'Medium', 'large' => 'Large', 'pill' => 'Pill']],
        'branding.fallback_image' => ['group' => 'branding', 'label' => 'Fallback Image', 'type' => 'image', 'default' => '', 'public' => true, 'description' => 'Upload a default image templates can use when content has no image.'],

        'analytics.enable_visitor_tracking' => ['group' => 'analytics', 'label' => 'Enable Visitor Tracking', 'type' => 'boolean', 'default' => true, 'public' => false, 'description' => 'Record visits for this tenant when platform analytics are enabled.'],
        'analytics.enable_cta_tracking'     => ['group' => 'analytics', 'label' => 'Enable CTA Tracking', 'type' => 'boolean', 'default' => true, 'public' => false, 'description' => 'Record CTA views, clicks, and submissions for this tenant when platform analytics are enabled.'],
        'analytics.retention_days'          => ['group' => 'analytics', 'label' => 'Retention Days', 'type' => 'integer', 'default' => 365, 'public' => false, 'description' => 'Enter how many days analytics data should be retained for this tenant.'],

        'notifications.cta_submission_email'     => ['group' => 'notifications', 'label' => 'CTA Submission Email', 'type' => 'email', 'default' => '', 'public' => false, 'description' => 'Enter where CTA submission notifications should be sent.'],
        'notifications.design_request_email'     => ['group' => 'notifications', 'label' => 'Design Request Email', 'type' => 'email', 'default' => '', 'public' => false, 'description' => 'Enter the inbox notified when a new design request is submitted.'],
        'notifications.reply_to_email'           => ['group' => 'notifications', 'label' => 'Reply-To Email', 'type' => 'email', 'default' => '', 'public' => false, 'description' => 'Enter where replies to tenant-branded email should go. This does not change the verified From address.'],
        'notifications.weekly_analytics_summary' => ['group' => 'notifications', 'label' => 'Weekly Analytics Summary', 'type' => 'boolean', 'default' => false, 'public' => false, 'description' => 'Send a weekly analytics summary through the platform email provider.'],

        'compliance.privacy_policy_url'    => ['group' => 'compliance', 'label' => 'Privacy Policy URL', 'type' => 'url', 'default' => '', 'public' => true, 'description' => 'Enter the tenant privacy policy URL.'],
        'compliance.terms_of_service_url'  => ['group' => 'compliance', 'label' => 'Terms of Service URL', 'type' => 'url', 'default' => '', 'public' => true, 'description' => 'Enter the tenant terms of service URL.'],
        'compliance.cookie_notice_enabled' => ['group' => 'compliance', 'label' => 'Cookie Notice Enabled', 'type' => 'boolean', 'default' => false, 'public' => true, 'description' => 'Show a cookie notice on tenant public pages where supported.'],
        'compliance.cookie_notice_text'    => ['group' => 'compliance', 'label' => 'Cookie Notice Text', 'type' => 'text', 'default' => '', 'public' => true, 'description' => 'Enter the cookie notice text for tenant public pages.'],
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
    public function groups(Tenant $tenant): array
    {
        $values = $this->values($tenant);
        $groups = [];

        foreach (self::DEFINITIONS as $key => $definition) {
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
    public function values(Tenant $tenant): array
    {
        $stored = $this->storedValues($tenant);

        return collect(self::DEFINITIONS)
            ->mapWithKeys(function (array $definition, string $key) use ($stored, $tenant): array {
                return [$key => array_key_exists($key, $stored) ? $stored[$key] : $this->defaultValue($tenant, $key, $definition['default'])];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function publicValues(Tenant $tenant): array
    {
        return $this->templateValues($tenant, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function templateValues(Tenant $tenant, bool $publicOnly = false): array
    {
        $values = $this->values($tenant);
        $stored = $this->storedValues($tenant);

        return collect(self::DEFINITIONS)
            ->filter(function (array $definition, string $key) use ($publicOnly, $stored): bool {
                if ($publicOnly && ! $definition['public']) {
                    return false;
                }

                return $definition['group'] !== 'branding' || array_key_exists($key, $stored);
            })
            ->mapWithKeys(fn (array $definition, string $key): array => [$key => $values[$key] ?? $definition['default']])
            ->all();
    }

    public function brandingUsesTemplateDefaults(Tenant $tenant): bool
    {
        return collect($this->storedValues($tenant))
            ->keys()
            ->doesntContain(fn (string $key): bool => str_starts_with($key, 'branding.'));
    }

    public function get(Tenant $tenant, string $key, mixed $fallback = null): mixed
    {
        $definition = self::DEFINITIONS[$key] ?? null;

        if (! $definition) {
            return $fallback;
        }

        return $this->values($tenant)[$key] ?? $definition['default'] ?? $fallback;
    }

    public function string(Tenant $tenant, string $key, string $fallback = ''): string
    {
        return trim((string) $this->get($tenant, $key, $fallback));
    }

    public function integer(Tenant $tenant, string $key, int $fallback = 0): int
    {
        return (int) $this->get($tenant, $key, $fallback);
    }

    public function boolean(Tenant $tenant, string $key, bool $fallback = false): bool
    {
        return filter_var($this->get($tenant, $key, $fallback), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function upsertGrouped(Tenant $tenant, array $payload, ?Authenticatable $actor = null): Tenant
    {
        return DB::transaction(function () use ($tenant, $payload, $actor): Tenant {
            $currentValues  = $this->values($tenant);
            $storedValues   = $this->storedValues($tenant);
            $changedEntries = [];

            foreach ($this->flattenGroupedPayload($payload) as $key => $value) {
                $definition    = self::DEFINITIONS[$key];
                $newValue      = $this->castValue(self::DEFINITIONS[$key]['type'], $value);
                $previousValue = $currentValues[$key] ?? self::DEFINITIONS[$key]['default'];

                TenantSystemSetting::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'key'       => $key,
                    ],
                    [
                        'group'     => $definition['group'],
                        'label'     => $definition['label'],
                        'type'      => $definition['type'],
                        'value'     => $newValue,
                        'is_public' => $definition['public'],
                    ],
                );

                if (! $this->valuesAreEqual($definition['type'], $previousValue, $newValue)) {
                    $changedEntries[] = [
                        'key'            => $key,
                        'previous_value' => $previousValue,
                        'new_value'      => $newValue,
                        'action'         => array_key_exists($key, $storedValues) ? 'updated' : 'created',
                    ];
                }
            }

            $values = $this->values($tenant);

            if (array_key_exists('profile.business_name', $values)) {
                $tenant->name = (string) $values['profile.business_name'];
            }

            if (array_key_exists('profile.timezone', $values)) {
                $tenant->timezone = (string) $values['profile.timezone'];
            }

            $tenant->save();
            $this->recordHistory($tenant, $changedEntries, $actor);

            return $tenant->refresh();
        });
    }

    public function resetBranding(Tenant $tenant, ?Authenticatable $actor = null): Tenant
    {
        return DB::transaction(function () use ($tenant, $actor): Tenant {
            $storedValues = $this->storedValues($tenant);
            $entries      = collect($storedValues)
                ->filter(fn (mixed $value, string $key): bool => str_starts_with($key, 'branding.'))
                ->map(fn (mixed $value, string $key): array => [
                    'key'            => $key,
                    'previous_value' => $value,
                    'new_value'      => null,
                    'action'         => 'deleted',
                ])
                ->values()
                ->all();

            TenantSystemSetting::query()
                ->where('tenant_id', $tenant->id)
                ->where('key', 'like', 'branding.%')
                ->delete();

            $this->recordHistory($tenant, $entries, $actor);

            return $tenant->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function history(Tenant $tenant, array $filters = []): array
    {
        if (! $this->historyTableExists()) {
            return [
                'data'       => [],
                'pagination' => $this->emptyPagination(),
            ];
        }

        $query = TenantSystemSettingHistory::query()
            ->where('tenant_id', $tenant->id);

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

    private function defaultValue(Tenant $tenant, string $key, mixed $default): mixed
    {
        $value = match ($key) {
            'profile.business_name' => $tenant->name,
            'profile.timezone'      => $tenant->timezone,
            default                 => $default,
        };

        return $value === null || $value === '' ? $default : $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function storedValues(Tenant $tenant): array
    {
        if (! $this->settingsTableExists()) {
            return is_array($tenant->settings) ? $tenant->settings : [];
        }

        return TenantSystemSetting::query()
            ->where('tenant_id', $tenant->id)
            ->get(['key', 'value'])
            ->mapWithKeys(fn (TenantSystemSetting $setting): array => [$setting->key => $setting->value])
            ->all();
    }

    private function castValue(string $type, mixed $value): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
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
     * @param  array<int, array{key: string, previous_value: mixed, new_value: mixed, action: string}>  $entries
     */
    private function recordHistory(Tenant $tenant, array $entries, ?Authenticatable $actor): void
    {
        if ($entries === [] || ! $this->historyTableExists()) {
            return;
        }

        foreach ($entries as $entry) {
            TenantSystemSettingHistory::query()->create([
                'tenant_id'          => $tenant->id,
                'setting_key'        => $entry['key'],
                'action'             => $entry['action'],
                'previous_value'     => $entry['previous_value'],
                'new_value'          => $entry['new_value'],
                'changed_by_user_id' => $actor?->getAuthIdentifier(),
                'changed_by_name'    => data_get($actor, 'name'),
                'changed_by_email'   => data_get($actor, 'email'),
                'changed_at'         => now(),
            ]);
        }
    }

    /**
     * @param  Builder<TenantSystemSettingHistory>  $query
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
     * @param  Builder<TenantSystemSettingHistory>  $query
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

    private function historyTableExists(): bool
    {
        try {
            return Schema::hasTable('tenant_system_setting_histories');
        } catch (\Throwable) {
            return false;
        }
    }

    private function settingsTableExists(): bool
    {
        try {
            return Schema::hasTable('tenant_system_settings');
        } catch (\Throwable) {
            return false;
        }
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

    private function groupLabel(string $group): string
    {
        return match ($group) {
            'profile'       => 'Profile',
            'website'       => 'Website',
            'seo'           => 'SEO',
            'branding'      => 'Branding',
            'analytics'     => 'Analytics',
            'notifications' => 'Notifications',
            'compliance'    => 'Compliance',
            default         => str($group)->headline()->value(),
        };
    }
}
