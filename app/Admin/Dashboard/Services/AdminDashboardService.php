<?php

namespace App\Admin\Dashboard\Services;

use App\Admin\SystemSettings\Services\SystemSettingService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminDashboardService
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $now         = Carbon::now();
        $weekStart   = $now->copy()->startOfWeek();
        $pendingWork = $this->pendingWork();

        return [
            'metrics'                => $this->metrics($weekStart),
            'pending_work'           => $pendingWork,
            'recent_tenant_activity' => $this->recentTenantActivity(),
            'platform_health'        => $this->platformHealth($pendingWork),
            'generated_at'           => $now->toISOString(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function metrics(Carbon $weekStart): array
    {
        return [
            'total_tenants'           => $this->count('tenants'),
            'active_tenants'          => $this->count('tenants', fn ($query) => $query->where('status', 'active')),
            'inactive_tenants'        => $this->count('tenants', fn ($query) => $query->where('status', 'inactive')),
            'new_tenants_this_week'   => $this->count('tenants', fn ($query) => $query->where('created_at', '>=', $weekStart)),
            'pending_design_requests' => $this->count('design_requests', fn ($query) => $query->whereIn('status', ['pending', 'under_review', 'changes_requested'])),
            'new_leads'               => $this->newLeadCount(),
            'published_templates'     => $this->count('templates', fn ($query) => $query->where('status', 'published')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pendingWork(): array
    {
        return [
            'pending_design_requests' => $this->count('design_requests', fn ($query) => $query->where('status', 'pending')),
            'under_review_requests'   => $this->count('design_requests', fn ($query) => $query->where('status', 'under_review')),
            'changes_requested'       => $this->count('design_requests', fn ($query) => $query->where('status', 'changes_requested')),
            'new_leads'               => $this->newLeadCount(),
            'oldest_pending_request'  => $this->oldestPendingDesignRequest(),
            'recent_design_requests'  => $this->recentDesignRequests(),
            'recent_leads'            => $this->recentLeads(),
        ];
    }

    /**
     * @param  callable(Builder): void|null  $callback
     */
    private function count(string $table, ?callable $callback = null): int
    {
        if (! $this->hasTable($table)) {
            return 0;
        }

        try {
            $query = DB::table($table);

            if ($this->hasColumn($table, 'deleted_at')) {
                $query->whereNull($table.'.deleted_at');
            }

            if ($callback) {
                $callback($query);
            }

            return (int) $query->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function newLeadCount(): int
    {
        if (! $this->hasTable('template_cta_submissions')) {
            return 0;
        }

        return (int) DB::table('template_cta_submissions')
            ->where('status', 'new')
            ->count();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function oldestPendingDesignRequest(): ?array
    {
        if (! $this->hasTable('design_requests')) {
            return null;
        }

        $hasTenants = $this->hasTable('tenants');
        $query      = DB::table('design_requests')
            ->where('design_requests.status', 'pending')
            ->orderBy('design_requests.created_at');

        if ($hasTenants) {
            $query->leftJoin('tenants', 'design_requests.tenant_id', '=', 'tenants.id');
        }

        $request = $query->first([
            'design_requests.id',
            'design_requests.title',
            'design_requests.status',
            'design_requests.created_at',
            $hasTenants ? 'tenants.name as tenant_name' : DB::raw('null as tenant_name'),
        ]);

        return $request ? $this->objectToArray($request) : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentDesignRequests(): array
    {
        if (! $this->hasTable('design_requests')) {
            return [];
        }

        $hasTenants = $this->hasTable('tenants');
        $query      = DB::table('design_requests')
            ->orderByDesc('design_requests.created_at')
            ->limit(5);

        if ($hasTenants) {
            $query->leftJoin('tenants', 'design_requests.tenant_id', '=', 'tenants.id');
        }

        return $query->get([
            'design_requests.id',
            'design_requests.title',
            'design_requests.status',
            'design_requests.created_at',
            $hasTenants ? 'tenants.name as tenant_name' : DB::raw('null as tenant_name'),
        ])
            ->map(fn (object $row): array => $this->objectToArray($row))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentLeads(): array
    {
        if (! $this->hasTable('template_cta_submissions') || ! $this->hasTable('templates')) {
            return [];
        }

        $hasTenants = $this->hasTable('tenants');
        $query      = DB::table('template_cta_submissions')
            ->join('templates', 'template_cta_submissions.template_id', '=', 'templates.id')
            ->orderByDesc('template_cta_submissions.created_at')
            ->limit(5);

        if ($hasTenants) {
            $query->leftJoin('tenants', 'templates.tenant_id', '=', 'tenants.id');
        }

        return $query->get([
            'template_cta_submissions.id',
            'template_cta_submissions.cta_type',
            'template_cta_submissions.status',
            'template_cta_submissions.created_at',
            'templates.name as template_name',
            $hasTenants ? 'tenants.name as tenant_name' : DB::raw('null as tenant_name'),
        ])
            ->map(fn (object $row): array => $this->objectToArray($row))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentTenantActivity(): array
    {
        if (! $this->hasTable('tenants')) {
            return [];
        }

        $hasUsers     = $this->hasTable('users');
        $hasTemplates = $this->hasTable('templates');
        $query        = DB::table('tenants');

        if ($hasUsers) {
            $userHasDeletedAt = $this->hasColumn('users', 'deleted_at');

            $query->leftJoin('users', function ($join) use ($userHasDeletedAt): void {
                $join->on('users.tenant_id', '=', 'tenants.id')
                    ->where('users.user_type', 'tenant');

                if ($userHasDeletedAt) {
                    $join->whereNull('users.deleted_at');
                }
            });
        }

        if ($hasTemplates) {
            $query->leftJoin('templates', 'templates.tenant_id', '=', 'tenants.id');
        }

        if ($this->hasColumn('tenants', 'deleted_at')) {
            $query->whereNull('tenants.deleted_at');
        }

        return $query
            ->groupBy('tenants.id', 'tenants.name', 'tenants.subdomain', 'tenants.status', 'tenants.created_at', 'tenants.updated_at')
            ->orderByDesc('tenants.updated_at')
            ->limit(6)
            ->get([
                'tenants.id',
                'tenants.name',
                'tenants.subdomain',
                'tenants.status',
                'tenants.created_at',
                'tenants.updated_at',
                $hasUsers ? DB::raw('count(distinct users.id) as users_count') : DB::raw('0 as users_count'),
                $hasTemplates ? DB::raw('count(distinct templates.id) as templates_count') : DB::raw('0 as templates_count'),
            ])
            ->map(fn (object $row): array => array_merge($this->objectToArray($row), [
                'users_count'     => (int) $row->users_count,
                'templates_count' => (int) $row->templates_count,
                'activity_label'  => $this->tenantActivityLabel($row),
            ]))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $pendingWork
     * @return array<string, mixed>
     */
    private function platformHealth(array $pendingWork): array
    {
        $items = [
            $this->queueHealth($pendingWork),
            $this->mailHealth(),
            $this->storageHealth(),
            $this->analyticsHealth(),
            $this->maintenanceHealth(),
        ];

        $critical = collect($items)->where('status', 'critical')->count();
        $warning  = collect($items)->where('status', 'warning')->count();

        return [
            'status'  => $critical > 0 ? 'critical' : ($warning > 0 ? 'warning' : 'healthy'),
            'summary' => $critical > 0
                ? "{$critical} critical platform checks need attention."
                : ($warning > 0 ? "{$warning} platform checks need review." : 'Core platform systems look ready.'),
            'items' => $items,
        ];
    }

    /**
     * @param  array<string, mixed>  $pendingWork
     * @return array<string, mixed>
     */
    private function queueHealth(array $pendingWork): array
    {
        $openWork = (int) $pendingWork['pending_design_requests']
            + (int) $pendingWork['under_review_requests']
            + (int) $pendingWork['changes_requested']
            + (int) $pendingWork['new_leads'];

        return [
            'key'         => 'queue',
            'label'       => 'Queue status',
            'status'      => $openWork >= 20 ? 'critical' : ($openWork > 0 ? 'warning' : 'healthy'),
            'value'       => "{$openWork} open",
            'description' => $openWork > 0
                ? 'Design requests or lead submissions are waiting for attention.'
                : 'No urgent queue items are waiting.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mailHealth(): array
    {
        $driver = $this->settings->string('email.mail_driver', 'smtp');
        $sender = $this->settings->string('email.sender_email');
        $host   = $this->settings->string('email.smtp_host');
        $ready  = $driver !== '' && $sender !== '' && ($driver !== 'smtp' || $host !== '');
        $status = $ready ? ($driver === 'log' || $driver === 'array' ? 'warning' : 'healthy') : 'critical';

        return [
            'key'         => 'mail',
            'label'       => 'Mail status',
            'status'      => $status,
            'value'       => $driver !== '' ? strtoupper($driver) : 'Not configured',
            'description' => $ready
                ? ($status === 'warning' ? 'Mail is configured for local/non-delivery mode.' : 'Mail delivery settings are configured.')
                : 'Mail driver, sender, or SMTP host needs configuration.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storageHealth(): array
    {
        $bytes       = $this->directorySize(storage_path('app/public'));
        $uploadLimit = $this->settings->integer('storage.maximum_upload_size', 4096);

        return [
            'key'         => 'storage',
            'label'       => 'Storage usage',
            'status'      => $bytes > 1024 * 1024 * 1024 ? 'warning' : 'healthy',
            'value'       => $this->formatBytes($bytes),
            'description' => "Public uploads are using {$this->formatBytes($bytes)}. Max upload is {$uploadLimit} KB.",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function analyticsHealth(): array
    {
        $moduleEnabled  = $this->settings->featureEnabled('enable_analytics_module');
        $visitorEnabled = $this->settings->boolean('analytics.enable_visitor_tracking', true);
        $ctaEnabled     = $this->settings->boolean('analytics.enable_cta_tracking', true);
        $recentEvents   = $this->recentAnalyticsEvents();
        $enabled        = $moduleEnabled && ($visitorEnabled || $ctaEnabled);

        return [
            'key'         => 'analytics',
            'label'       => 'Analytics capture',
            'status'      => $enabled ? ($recentEvents > 0 ? 'healthy' : 'warning') : 'critical',
            'value'       => $enabled ? "{$recentEvents} recent events" : 'Disabled',
            'description' => $enabled
                ? ($recentEvents > 0 ? 'Visitor or CTA tracking events were captured in the last 7 days.' : 'Tracking is enabled but no recent analytics events were captured.')
                : 'Analytics module or capture settings are disabled.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function maintenanceHealth(): array
    {
        $active = $this->settings->maintenanceActive();

        return [
            'key'         => 'maintenance',
            'label'       => 'Maintenance mode',
            'status'      => $active ? 'warning' : 'healthy',
            'value'       => $active ? 'On' : 'Off',
            'description' => $active
                ? 'Maintenance mode is currently active for affected platform areas.'
                : 'Maintenance mode is not active.',
        ];
    }

    private function recentAnalyticsEvents(): int
    {
        $since = Carbon::now()->subDays(7);

        return $this->count('visitor_visits', fn ($query) => $query->where('created_at', '>=', $since))
            + $this->count('cta_events', fn ($query) => $query->where('created_at', '>=', $since));
    }

    private function tenantActivityLabel(object $tenant): string
    {
        $createdAt = (string) ($tenant->created_at ?? '');
        $updatedAt = (string) ($tenant->updated_at ?? '');

        if ($createdAt !== '' && $updatedAt !== '') {
            try {
                $created = Carbon::parse($createdAt);
                $updated = Carbon::parse($updatedAt);

                if ($created->diffInMinutes($updated) < 5) {
                    return 'Created';
                }
            } catch (\Throwable) {
                // Fall through to the workspace status label when timestamps are malformed.
            }
        }

        if ((int) $tenant->templates_count === 0) {
            return 'Needs site setup';
        }

        return $tenant->status === 'active' ? 'Active workspace' : 'Inactive workspace';
    }

    private function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function objectToArray(object $row): array
    {
        return collect((array) $row)->map(function (mixed $value): mixed {
            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value)->toISOString();
            }

            return $value;
        })->all();
    }

    private function directorySize(string $path): int
    {
        if (! is_dir($path)) {
            return 0;
        }

        $size = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );
        } catch (\Throwable) {
            return 0;
        }

        foreach ($iterator as $file) {
            try {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $size;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / 1024 / 1024 / 1024, 1).' GB';
        }

        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 1).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}
