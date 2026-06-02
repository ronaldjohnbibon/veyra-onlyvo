<?php

namespace App\Modules\Posts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Posts\Http\Requests\PostFeaturedImageRequest;
use App\Modules\Posts\Http\Requests\PostRequest;
use App\Modules\Posts\Http\Resources\PostResource;
use App\Modules\Posts\Models\Post;
use App\Modules\Posts\Services\PostService;
use App\Modules\Templates\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function __construct(
        private readonly PostService $service,
    ) {}

    public function index(Request $request, string $template): JsonResponse
    {
        $templateRecord = $this->tenantTemplate($template);

        if (! $templateRecord) {
            return $this->error('Template not found.', 404);
        }

        $sorts = [
            'created_at'   => 'created_at',
            'published_at' => 'published_at',
            'status'       => 'status',
            'title'        => 'title',
        ];
        $sort       = (string) $request->input('sort', 'created_at');
        $sortColumn = $sorts[$sort] ?? 'created_at';
        $direction  = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $posts = $templateRecord->posts()
            ->filter([
                'search' => $request->input('search'),
                'status' => $request->input('status'),
            ])
            ->orderBy($sortColumn, $direction)
            ->orderBy('title')
            ->paginate(
                (int) $request->input('pageSize', 15),
                ['*'],
                'page',
                (int) $request->input('page', 1),
            );

        return $this->success(PostResource::collection($posts), 'Posts retrieved.');
    }

    public function store(PostRequest $request, string $template): JsonResponse
    {
        $templateRecord = $this->tenantTemplate($template);

        if (! $templateRecord) {
            return $this->error('Template not found.', 404);
        }

        $post = $this->service->create($templateRecord, $request->validated());

        return $this->success(new PostResource($post), 'Post saved.', 201);
    }

    public function uploadFeaturedImage(PostFeaturedImageRequest $request, string $template): JsonResponse
    {
        $templateRecord = $this->tenantTemplate($template);

        if (! $templateRecord) {
            return $this->error('Template not found.', 404);
        }

        $image = $request->file('image');

        abort_unless($image, 422);

        $path = $image->storePublicly(
            "posts/{$this->tenantId()}/{$templateRecord->id}/featured-images",
            'public',
        );

        return $this->success([
            'url'  => '/storage/'.$path,
            'path' => $path,
        ], 'Image uploaded.', 201);
    }

    public function show(string $template, string $post): JsonResponse
    {
        $templateRecord = $this->tenantTemplate($template);
        $postRecord     = $templateRecord ? $this->templatePost($templateRecord, $post) : null;

        if (! $templateRecord || ! $postRecord) {
            return $this->error('Post not found.', 404);
        }

        return $this->success(new PostResource($postRecord), 'Post retrieved.');
    }

    public function update(PostRequest $request, string $template, string $post): JsonResponse
    {
        $templateRecord = $this->tenantTemplate($template);
        $postRecord     = $templateRecord ? $this->templatePost($templateRecord, $post) : null;

        if (! $templateRecord || ! $postRecord) {
            return $this->error('Post not found.', 404);
        }

        $updated = $this->service->update($templateRecord, $postRecord, $request->validated());

        return $this->success(new PostResource($updated), 'Post saved.');
    }

    public function destroy(string $template, string $post): JsonResponse
    {
        $templateRecord = $this->tenantTemplate($template);
        $postRecord     = $templateRecord ? $this->templatePost($templateRecord, $post) : null;

        if (! $templateRecord || ! $postRecord) {
            return $this->error('Post not found.', 404);
        }

        $postRecord->delete();

        return $this->success(null, 'Post deleted.');
    }

    public function publish(string $template, string $post): JsonResponse
    {
        $templateRecord = $this->tenantTemplate($template);
        $postRecord     = $templateRecord ? $this->templatePost($templateRecord, $post) : null;

        if (! $templateRecord || ! $postRecord) {
            return $this->error('Post not found.', 404);
        }

        return $this->success(new PostResource($this->service->publish($postRecord)), 'Post published.');
    }

    public function unpublish(string $template, string $post): JsonResponse
    {
        $templateRecord = $this->tenantTemplate($template);
        $postRecord     = $templateRecord ? $this->templatePost($templateRecord, $post) : null;

        if (! $templateRecord || ! $postRecord) {
            return $this->error('Post not found.', 404);
        }

        return $this->success(new PostResource($this->service->unpublish($postRecord)), 'Post unpublished.');
    }

    private function tenantTemplate(string $template): ?Template
    {
        // Check template ownership.
        return Template::query()
            ->where('tenant_id', $this->tenantId())
            ->find($template);
    }

    private function templatePost(Template $template, string $post): ?Post
    {
        return $template->posts()->whereKey($post)->first();
    }

    private function tenantId(): string
    {
        $tenantId = Auth::user()?->tenant_id;

        abort_unless($tenantId, 403);

        return $tenantId;
    }
}
