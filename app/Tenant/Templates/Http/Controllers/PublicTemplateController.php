<?php

namespace App\Tenant\Templates\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\SystemSettings\Services\SystemSettingService;
use App\Tenant\SystemSettings\Services\TenantSystemSettingService;
use App\Tenant\Templates\Http\Resources\TemplateResource;
use App\Tenant\Templates\Models\Template;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Sprout\Contracts\Tenant as CurrentTenant;

class PublicTemplateController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $settings,
        private readonly TenantSystemSettingService $tenantSettings,
    ) {}

    public function show(string $slug, CurrentTenant $tenant): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_templates_module')) {
            return $this->error('Templates module is disabled.', 403);
        }

        if ($this->tenantSettings->string($tenant, 'website.site_status', 'live') !== 'live') {
            return $this->error('Public site is in draft mode.', 403);
        }

        $template = $this->publishedQuery()
            ->where('slug', $slug)
            ->first();

        if (! $template) {
            return $this->error('Published site not found.', 404);
        }

        return $this->success(new TemplateResource($template), 'Published site retrieved.');
    }

    public function defaultSite(CurrentTenant $tenant): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_templates_module')) {
            return $this->error('Templates module is disabled.', 403);
        }

        if ($this->tenantSettings->string($tenant, 'website.site_status', 'live') !== 'live') {
            return $this->error('Public site is in draft mode.', 403);
        }

        $homepageSlug = $this->tenantSettings->string($tenant, 'website.homepage_slug');
        $template     = $homepageSlug !== ''
            ? $this->publishedQuery()->where('slug', $homepageSlug)->first()
            : null;

        $template ??= $this->publishedQuery()
            ->orderByDesc('is_default')
            ->latest('updated_at')
            ->first();

        if (! $template) {
            return $this->error('Published site not found.', 404);
        }

        return $this->success(new TemplateResource($template), 'Published site retrieved.');
    }

    private function publishedQuery(): Builder
    {
        return Template::query()
            ->with(['tenant', 'websiteType'])
            ->where('status', 'published');
    }
}
