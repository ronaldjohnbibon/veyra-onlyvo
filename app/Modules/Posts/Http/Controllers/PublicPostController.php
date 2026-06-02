<?php

namespace App\Modules\Posts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Posts\Http\Resources\PostResource;
use App\Modules\Templates\Models\Template;
use Illuminate\Http\JsonResponse;

class PublicPostController extends Controller
{
    public function index(string $siteSlug): JsonResponse
    {
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
