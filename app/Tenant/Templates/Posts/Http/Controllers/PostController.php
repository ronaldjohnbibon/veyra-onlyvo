<?php

namespace App\Tenant\Templates\Posts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\AuditLogs\Services\TenantLogService;
use App\Tenant\SystemSettings\Services\SystemSettingService;
use App\Tenant\Templates\Models\Template;
use App\Tenant\Templates\Posts\Http\Requests\PostFeaturedImageRequest;
use App\Tenant\Templates\Posts\Http\Requests\PostRequest;
use App\Tenant\Templates\Posts\Http\Resources\PostResource;
use App\Tenant\Templates\Posts\Models\Post;
use App\Tenant\Templates\Posts\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function __construct(
        private readonly PostService $service,
        private readonly SystemSettingService $settings,
        private readonly TenantLogService $logs,
    ) {}

    public function index(Request $request, string $template): JsonResponse
    {
        if ($response = $this->postsDisabled()) {
            return $response;
        }

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
        $pageSize   = max(1, min(100, (int) $request->input('pageSize', 15)));
        $page       = max(1, (int) $request->input('page', 1));

        $posts = $templateRecord->posts()
            ->reorder()
            ->filter([
                'search' => $request->input('search'),
                'status' => $request->input('status'),
            ])
            ->orderBy($sortColumn, $direction)
            ->orderBy('title')
            ->paginate(
                $pageSize,
                ['*'],
                'page',
                $page,
            );

        return $this->success(PostResource::collection($posts), 'Posts retrieved.');
    }

    public function store(PostRequest $request, string $template): JsonResponse
    {
        if ($response = $this->postsDisabled()) {
            return $response;
        }

        $templateRecord = $this->tenantTemplate($template);

        if (! $templateRecord) {
            return $this->error('Template not found.', 404);
        }

        $post = $this->service->create($templateRecord, $request->validated());
        $this->logs->recordModel('post.created', $post, Auth::user(), $request, null, $post->attributesToArray(), 'post');

        return $this->success(new PostResource($post), 'Post saved.', 201);
    }

    public function uploadFeaturedImage(PostFeaturedImageRequest $request, string $template): JsonResponse
    {
        if ($response = $this->postsDisabled()) {
            return $response;
        }

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
        $this->logs->fileUpload('post.featured_image_uploaded', [
            'tenant_id'    => $this->tenantId(),
            'entity_type'  => 'template',
            'entity_id'    => $templateRecord->id,
            'entity_label' => $templateRecord->name,
            'metadata'     => [
                'path'          => $path,
                'original_name' => $image->getClientOriginalName(),
                'size'          => $image->getSize(),
                'mime_type'     => $image->getMimeType(),
            ],
        ], Auth::user(), $request);

        return $this->success([
            'url'  => '/storage/'.$path,
            'path' => $path,
        ], 'Image uploaded.', 201);
    }

    public function show(string $template, string $post): JsonResponse
    {
        if ($response = $this->postsDisabled()) {
            return $response;
        }

        $templateRecord = $this->tenantTemplate($template);
        $postRecord     = $templateRecord ? $this->templatePost($templateRecord, $post) : null;

        if (! $templateRecord || ! $postRecord) {
            return $this->error('Post not found.', 404);
        }

        return $this->success(new PostResource($postRecord), 'Post retrieved.');
    }

    public function update(PostRequest $request, string $template, string $post): JsonResponse
    {
        if ($response = $this->postsDisabled()) {
            return $response;
        }

        $templateRecord = $this->tenantTemplate($template);
        $postRecord     = $templateRecord ? $this->templatePost($templateRecord, $post) : null;

        if (! $templateRecord || ! $postRecord) {
            return $this->error('Post not found.', 404);
        }

        $previous = $postRecord->attributesToArray();
        $updated  = $this->service->update($templateRecord, $postRecord, $request->validated());
        $this->logs->recordModel('post.updated', $updated, Auth::user(), $request, $previous, $updated->attributesToArray(), 'post');

        return $this->success(new PostResource($updated), 'Post saved.');
    }

    public function destroy(string $template, string $post): JsonResponse
    {
        if ($response = $this->postsDisabled()) {
            return $response;
        }

        $templateRecord = $this->tenantTemplate($template);
        $postRecord     = $templateRecord ? $this->templatePost($templateRecord, $post) : null;

        if (! $templateRecord || ! $postRecord) {
            return $this->error('Post not found.', 404);
        }

        $previous = $postRecord->attributesToArray();
        $this->service->delete($postRecord);
        $this->logs->recordModel('post.deleted', $postRecord, Auth::user(), request(), $previous, null, 'post');

        return $this->success(null, 'Post deleted.');
    }

    public function publish(string $template, string $post): JsonResponse
    {
        if ($response = $this->postsDisabled()) {
            return $response;
        }

        $templateRecord = $this->tenantTemplate($template);
        $postRecord     = $templateRecord ? $this->templatePost($templateRecord, $post) : null;

        if (! $templateRecord || ! $postRecord) {
            return $this->error('Post not found.', 404);
        }

        $previous = $postRecord->attributesToArray();
        $updated  = $this->service->publish($postRecord);
        $this->logs->recordModel('post.published', $updated, Auth::user(), request(), $previous, $updated->attributesToArray(), 'post');

        return $this->success(new PostResource($updated), 'Post published.');
    }

    public function unpublish(string $template, string $post): JsonResponse
    {
        if ($response = $this->postsDisabled()) {
            return $response;
        }

        $templateRecord = $this->tenantTemplate($template);
        $postRecord     = $templateRecord ? $this->templatePost($templateRecord, $post) : null;

        if (! $templateRecord || ! $postRecord) {
            return $this->error('Post not found.', 404);
        }

        $previous = $postRecord->attributesToArray();
        $updated  = $this->service->unpublish($postRecord);
        $this->logs->recordModel('post.unpublished', $updated, Auth::user(), request(), $previous, $updated->attributesToArray(), 'post');

        return $this->success(new PostResource($updated), 'Post unpublished.');
    }

    private function tenantTemplate(string $template): ?Template
    {
        return Template::query()
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

    private function postsDisabled(): ?JsonResponse
    {
        return $this->settings->featureEnabled('enable_posts_module')
            ? null
            : $this->error('Posts module is disabled.', 403);
    }
}
