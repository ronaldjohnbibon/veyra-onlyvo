<?php

namespace App\Admin\Tenants\Services;

use App\Admin\Tenants\Http\Resources\TenantResource;
use App\Admin\Tenants\Models\Tenant;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TenantWorkspaceService
{
    /**
     * @return array<string, mixed>
     */
    public function workspace(Tenant $tenant, Request $request): array
    {
        $tenant->load('owner')->loadCount(['users', 'templates']);

        $baseUrl           = $this->tenantBaseUrl($tenant, $request);
        $publishedTemplate = $this->publishedTemplate((string) $tenant->id);

        return [
            'tenant'                 => (new TenantResource($tenant))->resolve(),
            'owner'                  => $this->owner($tenant),
            'users'                  => $this->users((string) $tenant->id),
            'domain'                 => $this->domain($tenant, $baseUrl, $publishedTemplate),
            'published_template'     => $publishedTemplate,
            'posts_summary'          => $this->postsSummary((string) $tenant->id),
            'leads_summary'          => $this->leadsSummary((string) $tenant->id),
            'design_request_summary' => $this->designRequestSummary((string) $tenant->id),
            'activity_timeline'      => $this->activityTimeline((string) $tenant->id, $tenant),
            'audit_history'          => $this->auditHistory((string) $tenant->id),
            'usage_metrics'          => $this->usageMetrics((string) $tenant->id),
            'feature_overrides'      => $this->featureOverrides((string) $tenant->id, $tenant->settings ?? []),
            'plan'                   => $this->plan($tenant),
            'actions'                => $this->actions($baseUrl),
            'generated_at'           => Carbon::now()->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function owner(Tenant $tenant): ?array
    {
        $owner = $tenant->owner;

        if (! $owner) {
            return null;
        }

        return [
            'id'                => $owner->id,
            'name'              => $owner->name,
            'first_name'        => $owner->first_name,
            'last_name'         => $owner->last_name,
            'email'             => $owner->email,
            'phone'             => $owner->phone,
            'is_active'         => (bool) $owner->is_active,
            'email_verified_at' => $owner->email_verified_at?->toISOString(),
            'created_at'        => $owner->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function users(string $tenantId): array
    {
        if (! $this->hasTable('users')) {
            return [];
        }

        return DB::table('users')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'first_name', 'last_name', 'email', 'phone', 'user_type', 'is_active', 'email_verified_at', 'created_at'])
            ->map(fn (object $user): array => $this->row($user))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function domain(Tenant $tenant, string $baseUrl, ?array $publishedTemplate): array
    {
        $workspaceUrl = rtrim($baseUrl, '/').'/dashboard';
        $publicUrl    = $publishedTemplate && ($publishedTemplate['status'] ?? '') === 'published'
            ? rtrim($baseUrl, '/').'/'.ltrim((string) $publishedTemplate['slug'], '/')
            : null;

        return [
            'subdomain'       => $tenant->subdomain,
            'workspace_url'   => $workspaceUrl,
            'public_site_url' => $publicUrl,
            'status'          => $tenant->status === 'active' && $tenant->subdomain !== '' ? 'connected' : 'attention',
            'status_label'    => $tenant->status === 'active' && $tenant->subdomain !== '' ? 'Subdomain ready' : 'Needs review',
            'status_detail'   => $tenant->status === 'active'
                ? 'Tenant subdomain is configured for workspace access.'
                : 'Tenant is inactive, so public and workspace access may be limited.',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function publishedTemplate(string $tenantId): ?array
    {
        if (! $this->hasTable('templates')) {
            return null;
        }

        $template = DB::table('templates')
            ->leftJoin('website_types', 'website_types.id', '=', 'templates.website_type_id')
            ->where('templates.tenant_id', $tenantId)
            ->where('templates.status', 'published')
            ->orderByDesc('templates.is_default')
            ->orderByDesc('templates.updated_at')
            ->first([
                'templates.id',
                'templates.name',
                'templates.business_name',
                'templates.slug',
                'templates.template_key',
                'templates.status',
                'templates.is_default',
                'templates.updated_at',
                'website_types.name as website_type_name',
            ]);

        if (! $template) {
            return null;
        }

        $row                = $this->row($template);
        $row['posts_count'] = $this->hasPostTables()
            ? $this->postQuery($tenantId)->where('posts.template_id', $row['id'])->count()
            : 0;
        $row['leads_count'] = $this->hasLeadTables()
            ? $this->leadQuery($tenantId)->where('template_cta_submissions.template_id', $row['id'])->count()
            : 0;

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function postsSummary(string $tenantId): array
    {
        if (! $this->hasPostTables()) {
            return [
                'total'     => 0,
                'published' => 0,
                'draft'     => 0,
                'recent'    => [],
            ];
        }

        $query = $this->postQuery($tenantId);

        return [
            'total'     => (clone $query)->count(),
            'published' => (clone $query)->where('posts.status', 'published')->count(),
            'draft'     => (clone $query)->where('posts.status', 'draft')->count(),
            'recent'    => (clone $query)
                ->orderByDesc('posts.updated_at')
                ->limit(5)
                ->get(['posts.id', 'posts.title', 'posts.slug', 'posts.status', 'posts.published_at', 'posts.updated_at'])
                ->map(fn (object $post): array => $this->row($post))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function leadsSummary(string $tenantId): array
    {
        if (! $this->hasLeadTables()) {
            return [
                'total'     => 0,
                'new'       => 0,
                'contacted' => 0,
                'archived'  => 0,
                'recent'    => [],
            ];
        }

        $query = $this->leadQuery($tenantId);

        return [
            'total'     => (clone $query)->count(),
            'new'       => (clone $query)->where('template_cta_submissions.status', 'new')->count(),
            'contacted' => (clone $query)->where('template_cta_submissions.status', 'contacted')->count(),
            'archived'  => (clone $query)->where('template_cta_submissions.status', 'archived')->count(),
            'recent'    => (clone $query)
                ->orderByDesc('template_cta_submissions.created_at')
                ->limit(5)
                ->get([
                    'template_cta_submissions.id',
                    'template_cta_submissions.cta_type',
                    'template_cta_submissions.status',
                    'template_cta_submissions.created_at',
                    'templates.name as template_name',
                    'templates.business_name',
                ])
                ->map(fn (object $lead): array => array_merge($this->row($lead), [
                    'summary' => $this->leadLabel((string) $lead->cta_type),
                ]))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function designRequestSummary(string $tenantId): array
    {
        if (! $this->hasTable('design_requests')) {
            return [
                'total'             => 0,
                'pending'           => 0,
                'under_review'      => 0,
                'changes_requested' => 0,
                'completed'         => 0,
                'recent'            => [],
            ];
        }

        $query = DB::table('design_requests')->where('tenant_id', $tenantId);

        return [
            'total'             => (clone $query)->count(),
            'pending'           => (clone $query)->where('status', 'pending')->count(),
            'under_review'      => (clone $query)->where('status', 'under_review')->count(),
            'changes_requested' => (clone $query)->where('status', 'changes_requested')->count(),
            'completed'         => (clone $query)->where('status', 'completed')->count(),
            'recent'            => (clone $query)
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get(['id', 'title', 'status', 'created_at', 'updated_at'])
                ->map(fn (object $request): array => $this->row($request))
                ->all(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function activityTimeline(string $tenantId, Tenant $tenant): array
    {
        $items = [[
            'id'          => 'tenant-created-'.$tenant->id,
            'type'        => 'tenant',
            'title'       => 'Tenant created',
            'description' => $tenant->name.' workspace was created.',
            'occurred_at' => $tenant->created_at?->toISOString(),
        ]];

        $items = array_merge(
            $items,
            $this->templateTimeline($tenantId),
            $this->postTimeline($tenantId),
            $this->leadTimeline($tenantId),
            $this->designRequestTimeline($tenantId),
            $this->auditTimeline($tenantId),
        );

        return collect($items)
            ->filter(fn (array $item): bool => filled($item['occurred_at'] ?? null))
            ->sortByDesc('occurred_at')
            ->take(20)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function auditHistory(string $tenantId): array
    {
        if (! $this->hasTable('tenant_system_setting_histories')) {
            return [];
        }

        return DB::table('tenant_system_setting_histories')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('changed_at')
            ->limit(10)
            ->get(['id', 'setting_key', 'action', 'changed_by_name', 'changed_by_email', 'changed_at'])
            ->map(fn (object $history): array => $this->row($history))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function usageMetrics(string $tenantId): array
    {
        $sevenDaysAgo = Carbon::now()->subDays(6)->toDateString();
        $today        = Carbon::now()->toDateString();

        return [
            'users'                  => $this->usersCount($tenantId),
            'templates'              => $this->templatesCount($tenantId),
            'published_templates'    => $this->templatesCount($tenantId, 'published'),
            'posts'                  => $this->postQuery($tenantId)->count(),
            'leads'                  => $this->leadQuery($tenantId)->count(),
            'design_requests'        => $this->hasTable('design_requests') ? DB::table('design_requests')->where('tenant_id', $tenantId)->count() : 0,
            'visits_last_7_days'     => $this->hasTable('visitor_visits') ? DB::table('visitor_visits')->where('tenant_id', $tenantId)->where('visit_date', '>=', $sevenDaysAgo)->count() : 0,
            'visits_today'           => $this->hasTable('visitor_visits') ? DB::table('visitor_visits')->where('tenant_id', $tenantId)->where('visit_date', $today)->count() : 0,
            'cta_events_last_7_days' => $this->hasTable('cta_events') ? DB::table('cta_events')->where('tenant_id', $tenantId)->where('event_date', '>=', $sevenDaysAgo)->count() : 0,
            'storage_bytes'          => $this->storageBytes($tenantId),
            'storage_label'          => $this->formatBytes($this->storageBytes($tenantId)),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<int, array<string, mixed>>
     */
    private function featureOverrides(string $tenantId, array $settings): array
    {
        $overrides = collect($settings)
            ->filter(fn (mixed $value, string $key): bool => str_starts_with($key, 'feature_flags.') || str_starts_with($key, 'analytics.'))
            ->map(fn (mixed $value, string $key): array => [
                'key'    => $key,
                'value'  => $value,
                'source' => 'tenant_settings_json',
            ]);

        if ($this->hasTable('tenant_system_settings')) {
            DB::table('tenant_system_settings')
                ->where('tenant_id', $tenantId)
                ->where(function (Builder $query): void {
                    $query->where('key', 'like', 'feature_flags.%')
                        ->orWhere('key', 'like', 'analytics.%')
                        ->orWhere('key', 'like', 'website.%');
                })
                ->orderBy('key')
                ->get(['key', 'value'])
                ->each(function (object $setting) use ($overrides): void {
                    $overrides->put((string) $setting->key, [
                        'key'    => (string) $setting->key,
                        'value'  => $this->decodeValue($setting->value),
                        'source' => 'tenant_system_settings',
                    ]);
                });
        }

        return $overrides->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function plan(Tenant $tenant): array
    {
        $settings    = $tenant->settings ?? [];
        $trialDays   = (int) ($settings['trial_days'] ?? 0);
        $trialEndsAt = $trialDays > 0 ? $tenant->created_at?->copy()->addDays($trialDays) : null;

        return [
            'plan_name'     => (string) ($settings['plan'] ?? 'Workspace'),
            'trial_days'    => $trialDays,
            'trial_ends_at' => $trialEndsAt?->toISOString(),
            'trial_status'  => $trialEndsAt
                ? ($trialEndsAt->isPast() ? 'expired' : 'active')
                : 'not_set',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function actions(string $baseUrl): array
    {
        return [
            'open_workspace' => [
                'available' => true,
                'url'       => rtrim($baseUrl, '/').'/dashboard',
            ],
            'impersonation' => [
                'available' => false,
                'url'       => null,
                'reason'    => 'Impersonation requires a dedicated audited token flow before activation.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function templateTimeline(string $tenantId): array
    {
        if (! $this->hasTable('templates')) {
            return [];
        }

        return DB::table('templates')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get(['id', 'name', 'business_name', 'status', 'updated_at'])
            ->map(fn (object $template): array => [
                'id'          => 'template-'.$template->id,
                'type'        => 'template',
                'title'       => 'Template '.$this->titleText((string) $template->status),
                'description' => (string) ($template->business_name ?: $template->name),
                'occurred_at' => $this->dateString($template->updated_at),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function postTimeline(string $tenantId): array
    {
        if (! $this->hasPostTables()) {
            return [];
        }

        return $this->postQuery($tenantId)
            ->orderByDesc('posts.updated_at')
            ->limit(5)
            ->get(['posts.id', 'posts.title', 'posts.status', 'posts.updated_at'])
            ->map(fn (object $post): array => [
                'id'          => 'post-'.$post->id,
                'type'        => 'post',
                'title'       => 'Post '.$this->titleText((string) $post->status),
                'description' => (string) $post->title,
                'occurred_at' => $this->dateString($post->updated_at),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function leadTimeline(string $tenantId): array
    {
        if (! $this->hasLeadTables()) {
            return [];
        }

        return $this->leadQuery($tenantId)
            ->orderByDesc('template_cta_submissions.created_at')
            ->limit(5)
            ->get(['template_cta_submissions.id', 'template_cta_submissions.cta_type', 'template_cta_submissions.status', 'template_cta_submissions.created_at'])
            ->map(fn (object $lead): array => [
                'id'          => 'lead-'.$lead->id,
                'type'        => 'lead',
                'title'       => 'Lead '.$this->titleText((string) $lead->status),
                'description' => $this->leadLabel((string) $lead->cta_type),
                'occurred_at' => $this->dateString($lead->created_at),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function designRequestTimeline(string $tenantId): array
    {
        if (! $this->hasTable('design_requests')) {
            return [];
        }

        return DB::table('design_requests')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get(['id', 'title', 'status', 'updated_at'])
            ->map(fn (object $request): array => [
                'id'          => 'design-request-'.$request->id,
                'type'        => 'design_request',
                'title'       => 'Design request '.$this->titleText((string) $request->status),
                'description' => (string) $request->title,
                'occurred_at' => $this->dateString($request->updated_at),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function auditTimeline(string $tenantId): array
    {
        return collect($this->auditHistory($tenantId))
            ->map(fn (array $history): array => [
                'id'          => 'audit-'.$history['id'],
                'type'        => 'audit',
                'title'       => 'Setting '.$this->titleText((string) $history['action']),
                'description' => (string) $history['setting_key'],
                'occurred_at' => (string) $history['changed_at'],
            ])
            ->all();
    }

    private function postQuery(string $tenantId): Builder
    {
        if (! $this->hasPostTables()) {
            return $this->emptyQuery();
        }

        return DB::table('posts')
            ->join('templates', 'templates.id', '=', 'posts.template_id')
            ->where('templates.tenant_id', $tenantId);
    }

    private function leadQuery(string $tenantId): Builder
    {
        if (! $this->hasLeadTables()) {
            return $this->emptyQuery();
        }

        return DB::table('template_cta_submissions')
            ->join('templates', 'templates.id', '=', 'template_cta_submissions.template_id')
            ->where('templates.tenant_id', $tenantId);
    }

    private function hasPostTables(): bool
    {
        return $this->hasTable('posts') && $this->hasTable('templates');
    }

    private function hasLeadTables(): bool
    {
        return $this->hasTable('template_cta_submissions') && $this->hasTable('templates');
    }

    private function emptyQuery(): Builder
    {
        return DB::query()
            ->fromRaw('(select 1 as id) as empty_result')
            ->whereRaw('1 = 0');
    }

    private function usersCount(string $tenantId): int
    {
        return $this->hasTable('users')
            ? DB::table('users')->where('tenant_id', $tenantId)->whereNull('deleted_at')->count()
            : 0;
    }

    private function templatesCount(string $tenantId, ?string $status = null): int
    {
        if (! $this->hasTable('templates')) {
            return 0;
        }

        return DB::table('templates')
            ->where('tenant_id', $tenantId)
            ->when($status, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->count();
    }

    private function storageBytes(string $tenantId): int
    {
        $files = $this->hasTable('design_request_files') && $this->hasTable('design_requests')
            ? DB::table('design_request_files')
                ->join('design_requests', 'design_requests.id', '=', 'design_request_files.design_request_id')
                ->where('design_requests.tenant_id', $tenantId)
                ->sum('design_request_files.size')
            : 0;

        return (int) $files;
    }

    private function tenantBaseUrl(Tenant $tenant, Request $request): string
    {
        $host        = $request->getHost();
        $parts       = explode('.', $host);
        $port        = $request->getPort();
        $portSegment = in_array($port, [80, 443], true) ? '' : ':'.$port;

        if (count($parts) > 2 && in_array($parts[0], ['admin', 'www'], true)) {
            array_shift($parts);
            $host = implode('.', $parts);
        }

        return $request->getScheme().'://'.$tenant->subdomain.'.'.$host.$portSegment;
    }

    private function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function row(object $row): array
    {
        return collect((array) $row)->map(function (mixed $value): mixed {
            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value)->toISOString();
            }

            return $value;
        })->all();
    }

    private function dateString(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse((string) $value)->toISOString();
    }

    private function decodeValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    private function leadLabel(string $ctaType): string
    {
        return $this->titleText(str_replace('_', ' ', $ctaType));
    }

    private function titleText(string $value): string
    {
        return Str::title(str_replace('_', ' ', $value));
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
