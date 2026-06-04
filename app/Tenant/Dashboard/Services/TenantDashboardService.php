<?php

namespace App\Tenant\Dashboard\Services;

use App\Tenant\DesignRequests\Models\DesignRequest;
use App\Tenant\SystemSettings\Services\SystemSettingService;
use App\Tenant\SystemSettings\Services\TenantSystemSettingService;
use App\Tenant\Templates\Models\Template;
use App\Tenant\Templates\Models\TemplateCtaSubmission;
use App\Tenant\Templates\Posts\Models\Post;
use App\Tenant\Tenants\Models\Tenant;
use Illuminate\Support\Facades\DB;

class TenantDashboardService
{
    public function __construct(
        private readonly SystemSettingService $settings,
        private readonly TenantSystemSettingService $tenantSettings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(Tenant $tenant, string $baseUrl): array
    {
        $tenantId        = (string) $tenant->id;
        $values          = $this->tenantSettings->values($tenant);
        $analyticsActive = $this->settings->featureEnabled('enable_analytics_module');
        $postsActive     = $this->settings->featureEnabled('enable_posts_module');
        $requestsActive  = $this->settings->featureEnabled('enable_design_requests_module');
        $formsActive     = $this->settings->featureEnabled('enable_cta_forms');

        $defaultTemplate = Template::query()
            ->with('websiteType')
            ->where('is_default', true)
            ->orderByDesc('updated_at')
            ->first();

        $latestPublishedTemplate = Template::query()
            ->with('websiteType')
            ->where('status', 'published')
            ->orderByDesc('is_default')
            ->latest('updated_at')
            ->first();

        $activeTemplate = $defaultTemplate ?? $latestPublishedTemplate;
        $siteIsLive     = (string) ($values['website.site_status'] ?? 'live') === 'live';

        return [
            'site' => [
                'name'              => $this->siteName($tenant, $activeTemplate, $values),
                'status'            => (string) ($values['website.site_status'] ?? 'live'),
                'public_url'        => $this->publicTemplateUrl($baseUrl, $activeTemplate, $siteIsLive),
                'tracking_enabled'  => $analyticsActive && (bool) ($values['analytics.enable_visitor_tracking'] ?? true),
                'cta_forms_enabled' => $formsActive && (bool) ($values['website.contact_form_enabled'] ?? true),
            ],
            'default_template' => $activeTemplate ? $this->templateSummary($activeTemplate, $baseUrl, $siteIsLive) : null,
            'metrics'          => $this->metrics($tenantId, $analyticsActive, $postsActive, $requestsActive),
            'recent_posts'     => $postsActive ? $this->recentPosts($tenantId, $baseUrl) : [],
            'recent_leads'     => $formsActive ? $this->recentLeads($tenantId) : [],
            'pending_design_requests' => $requestsActive ? $this->pendingDesignRequests($tenantId) : [],
            'launch_checklist' => $this->launchChecklist($values, $activeTemplate, $tenantId, $analyticsActive, $postsActive),
            'module_status'    => [
                'analytics'       => $analyticsActive,
                'posts'           => $postsActive,
                'design_requests' => $requestsActive,
                'cta_forms'       => $formsActive,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function siteName(Tenant $tenant, ?Template $template, array $values): string
    {
        return (string) (
            $values['profile.business_name']
            ?: $template?->business_name
            ?: $tenant->name
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function metrics(string $tenantId, bool $analyticsActive, bool $postsActive, bool $requestsActive): array
    {
        $sevenDaysAgo = now()->subDays(6)->toDateString();
        $today        = now()->toDateString();

        return [
            'recent_visits'           => $analyticsActive ? DB::table('visitor_visits')->where('tenant_id', $tenantId)->where('visit_date', '>=', $sevenDaysAgo)->count() : 0,
            'today_visits'            => $analyticsActive ? DB::table('visitor_visits')->where('tenant_id', $tenantId)->where('visit_date', $today)->count() : 0,
            'recent_cta_events'       => $analyticsActive ? DB::table('cta_events')->where('tenant_id', $tenantId)->where('event_date', '>=', $sevenDaysAgo)->count() : 0,
            'today_cta_events'        => $analyticsActive ? DB::table('cta_events')->where('tenant_id', $tenantId)->where('event_date', $today)->count() : 0,
            'new_leads'               => $this->leadQuery($tenantId)->where('template_cta_submissions.status', 'new')->count(),
            'recent_leads'            => $this->leadQuery($tenantId)->whereDate('template_cta_submissions.created_at', '>=', $sevenDaysAgo)->count(),
            'published_posts'         => $postsActive ? $this->postQuery($tenantId)->where('posts.status', 'published')->count() : 0,
            'draft_posts'             => $postsActive ? $this->postQuery($tenantId)->where('posts.status', 'draft')->count() : 0,
            'pending_design_requests' => $requestsActive ? DesignRequest::query()->where('tenant_id', $tenantId)->whereIn('status', ['pending', 'under_review', 'approved'])->count() : 0,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentPosts(string $tenantId, string $baseUrl): array
    {
        return $this->postQuery($tenantId)
            ->select(['posts.*'])
            ->with('template')
            ->latest('posts.updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Post $post): array => [
                'id'           => $post->id,
                'template_id'  => $post->template_id,
                'template_name'=> $post->template?->business_name ?: $post->template?->name,
                'site_slug'    => $post->template?->slug,
                'title'        => $post->title,
                'slug'         => $post->slug,
                'status'       => $post->status,
                'public_url'   => $post->template && $post->status === 'published' ? $this->postUrl($baseUrl, $post->template->slug, $post->slug) : null,
                'published_at' => $post->published_at,
                'updated_at'   => $post->updated_at,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentLeads(string $tenantId): array
    {
        return $this->leadQuery($tenantId)
            ->select([
                'template_cta_submissions.*',
                'templates.name as template_name',
                'templates.business_name',
            ])
            ->latest('template_cta_submissions.created_at')
            ->limit(5)
            ->get()
            ->map(fn (object $lead): array => [
                'id'            => (string) $lead->id,
                'cta_type'      => (string) $lead->cta_type,
                'status'        => (string) $lead->status,
                'summary'       => $this->leadSummary($lead->payload),
                'template_name' => (string) ($lead->business_name ?: $lead->template_name),
                'created_at'    => $lead->created_at,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pendingDesignRequests(string $tenantId): array
    {
        return DesignRequest::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'under_review', 'approved'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (DesignRequest $request): array => [
                'id'         => $request->id,
                'title'      => $request->title,
                'status'     => $request->status,
                'created_at' => $request->created_at,
                'updated_at' => $request->updated_at,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<int, array<string, mixed>>
     */
    private function launchChecklist(array $values, ?Template $template, string $tenantId, bool $analyticsActive, bool $postsActive): array
    {
        return [
            [
                'key'         => 'publish_site',
                'label'       => 'Publish a default site',
                'description' => 'Choose a website design and make it the default public site.',
                'completed'   => (bool) ($template && $template->status === 'published' && $template->is_default),
                'to'          => '/templates',
            ],
            [
                'key'         => 'site_live',
                'label'       => 'Set the site live',
                'description' => 'Confirm your public website is marked live.',
                'completed'   => (string) ($values['website.site_status'] ?? 'live') === 'live',
                'to'          => '/system-settings',
            ],
            [
                'key'         => 'brand_profile',
                'label'       => 'Complete brand basics',
                'description' => 'Add business name, logo, and public contact details.',
                'completed'   => $this->hasBrandProfile($values, $template),
                'to'          => '/system-settings',
            ],
            [
                'key'         => 'seo',
                'label'       => 'Add SEO defaults',
                'description' => 'Set a default page title and search description.',
                'completed'   => filled($values['seo.default_meta_title'] ?? null) && filled($values['seo.default_meta_description'] ?? null),
                'to'          => '/system-settings',
            ],
            [
                'key'         => 'analytics',
                'label'       => 'Turn on tracking',
                'description' => 'Record visits and CTA activity for growth reporting.',
                'completed'   => $analyticsActive && (bool) ($values['analytics.enable_visitor_tracking'] ?? true) && (bool) ($values['analytics.enable_cta_tracking'] ?? true),
                'to'          => '/system-settings',
            ],
            [
                'key'         => 'first_post',
                'label'       => 'Publish a first post',
                'description' => 'Add fresh content to support launch and discovery.',
                'completed'   => $postsActive && $this->postQuery($tenantId)->where('posts.status', 'published')->exists(),
                'to'          => '/posts',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function hasBrandProfile(array $values, ?Template $template): bool
    {
        return (filled($values['profile.business_name'] ?? null) || filled($template?->business_name))
            && (filled($values['profile.logo'] ?? null) || filled($template?->logo))
            && (
                filled($values['profile.contact_email'] ?? null)
                || filled($template?->contact_info['email'] ?? null)
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function templateSummary(Template $template, string $baseUrl, bool $siteIsLive): array
    {
        return [
            'id'                => $template->id,
            'name'              => $template->name,
            'business_name'     => $template->business_name,
            'slug'              => $template->slug,
            'status'            => $template->status,
            'is_default'        => (bool) $template->is_default,
            'public_url'        => $this->publicTemplateUrl($baseUrl, $template, $siteIsLive),
            'website_type_name' => $template->websiteType?->name,
            'updated_at'        => $template->updated_at,
        ];
    }

    private function postQuery(string $tenantId)
    {
        return Post::query()
            ->join('templates', 'templates.id', '=', 'posts.template_id')
            ->where('templates.tenant_id', $tenantId);
    }

    private function leadQuery(string $tenantId)
    {
        return TemplateCtaSubmission::query()
            ->join('templates', 'templates.id', '=', 'template_cta_submissions.template_id')
            ->where('templates.tenant_id', $tenantId);
    }

    private function leadSummary(mixed $payload): string
    {
        $data = is_string($payload) ? json_decode($payload, true) : $payload;

        if (! is_array($data)) {
            return 'New form submission';
        }

        $values = collect($data)
            ->filter(fn (mixed $value): bool => is_scalar($value) && trim((string) $value) !== '')
            ->take(2)
            ->map(fn (mixed $value): string => str((string) $value)->limit(48)->toString())
            ->values();

        return $values->isNotEmpty() ? $values->join(' - ') : 'New form submission';
    }

    private function templateUrl(string $baseUrl, string $slug): string
    {
        return rtrim($baseUrl, '/').'/'.ltrim($slug, '/');
    }

    private function publicTemplateUrl(string $baseUrl, ?Template $template, bool $siteIsLive): ?string
    {
        if (! $template || $template->status !== 'published' || ! $siteIsLive) {
            return null;
        }

        return $this->templateUrl($baseUrl, $template->slug);
    }

    private function postUrl(string $baseUrl, string $siteSlug, string $postSlug): string
    {
        return rtrim($baseUrl, '/').'/'.ltrim($siteSlug, '/').'/posts/'.ltrim($postSlug, '/');
    }
}
