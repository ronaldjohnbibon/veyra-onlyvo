<?php

namespace App\Tenant\Templates\Posts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\SystemSettings\Services\SystemSettingService;
use App\Tenant\SystemSettings\Services\TenantSystemSettingService;
use App\Tenant\Templates\Models\Template;
use App\Tenant\Templates\Posts\Http\Resources\PostResource;
use Illuminate\Http\JsonResponse;

class PublicPostController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $settings,
        private readonly TenantSystemSettingService $tenantSettings,
    ) {}

    public function index(string $siteSlug): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_posts_module')) {
            return $this->error('Posts module is disabled.', 403);
        }

        $template = $this->publishedTemplate($siteSlug);

        if (! $template || $this->tenantSettings->string($template->tenant, 'website.site_status', 'live') !== 'live') {
            return $this->error('Published site not found.', 404);
        }

        // Show published posts only.
        $posts = $template->posts()
            ->with('template.tenant')
            ->whereIn('status', ['published', 'scheduled'])
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->get();

        return $this->success(PostResource::collection($posts), 'Published posts retrieved.');
    }

    public function show(string $siteSlug, string $postSlug): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_posts_module')) {
            return $this->error('Posts module is disabled.', 403);
        }

        $template = $this->publishedTemplate($siteSlug);
        $post     = $template
            ? $template->posts()
                ->whereIn('status', ['published', 'scheduled'])
                ->where('slug', $postSlug)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->first()
            : null;

        if (
            ! $template
            || $this->tenantSettings->string($template->tenant, 'website.site_status', 'live') !== 'live'
            || ! $post
        ) {
            return $this->error('Published post not found.', 404);
        }

        return $this->success(new PostResource($post->load('template.tenant')), 'Published post retrieved.');
    }

    private function publishedTemplate(string $siteSlug): ?Template
    {
        return Template::query()
            ->with('tenant')
            ->where('status', 'published')
            ->where('slug', $siteSlug)
            ->first();
    }
}
