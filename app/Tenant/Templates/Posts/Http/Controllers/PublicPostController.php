<?php

namespace App\Tenant\Templates\Posts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\SystemSettings\Services\SystemSettingService;
use App\Tenant\Templates\Posts\Http\Resources\PostResource;
use App\Tenant\Templates\Models\Template;
use Illuminate\Http\JsonResponse;

class PublicPostController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    public function index(string $siteSlug): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_posts_module')) {
            return $this->error('Posts module is disabled.', 403);
        }

        $template = $this->publishedTemplate($siteSlug);

        if (! $template) {
            return $this->error('Published site not found.', 404);
        }

        // Show published posts only.
        $posts = $template->posts()
            ->where('status', 'published')
            ->whereNotNull('published_at')
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
                ->where('status', 'published')
                ->where('slug', $postSlug)
                ->whereNotNull('published_at')
                ->first()
            : null;

        if (! $template || ! $post) {
            return $this->error('Published post not found.', 404);
        }

        return $this->success(new PostResource($post), 'Published post retrieved.');
    }

    private function publishedTemplate(string $siteSlug): ?Template
    {
        return Template::query()
            ->where('status', 'published')
            ->where('slug', $siteSlug)
            ->first();
    }
}
