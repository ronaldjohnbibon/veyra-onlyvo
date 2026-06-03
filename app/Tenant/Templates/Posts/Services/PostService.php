<?php

namespace App\Tenant\Templates\Posts\Services;

use App\Tenant\Templates\Models\Template;
use App\Tenant\Templates\Posts\Models\Post;
use Illuminate\Support\Str;

class PostService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Template $template, array $data): Post
    {
        // Create tenant post.
        return $template->posts()->create($this->payload($template, $data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Template $template, Post $post, array $data): Post
    {
        $post->update($this->payload($template, $data, $post));

        return $post->fresh();
    }

    public function publish(Post $post): Post
    {
        $post->update([
            'status'       => 'published',
            'published_at' => $post->published_at ?? now(),
        ]);

        return $post->fresh();
    }

    public function unpublish(Post $post): Post
    {
        $post->update([
            'status'       => 'draft',
            'published_at' => null,
        ]);

        return $post->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(Template $template, array $data, ?Post $post = null): array
    {
        $status = $data['status'] ?? $post?->status ?? 'draft';

        return [
            'title'          => trim((string) $data['title']),
            'slug'           => $this->slugForTemplate($template, (string) $data['title'], $post?->id),
            'content'        => trim((string) $data['content']),
            'featured_image' => $this->featuredImagePath($data['featured_image'] ?? null),
            'status'         => $status,
            'published_at'   => $status === 'published' ? ($post?->published_at ?? now()) : null,
        ];
    }

    private function slugForTemplate(Template $template, string $value, ?string $ignoreId = null): string
    {
        $baseSlug  = Str::slug($value) ?: 'post';
        $slug      = $baseSlug;
        $nextIndex = 2;

        while ($this->slugExists($template, $slug, $ignoreId)) {
            $slug = $baseSlug.'-'.$nextIndex++;
        }

        return $slug;
    }

    private function slugExists(Template $template, string $slug, ?string $ignoreId = null): bool
    {
        return Post::query()
            ->where('template_id', $template->id)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    private function featuredImagePath(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $parts = parse_url($value) ?: [];
        $path  = $parts['path'] ?? $value;

        if (($parts['scheme'] ?? null) && ! $this->isLocalStorageHost($parts['host'] ?? null)) {
            return $value;
        }

        // Store local public-disk images as paths so tenant hosts resolve them correctly.
        if (str_starts_with($path, '/storage/')) {
            return ltrim(substr($path, strlen('/storage/')), '/');
        }

        if (str_starts_with($path, 'storage/')) {
            return substr($path, strlen('storage/'));
        }

        return $value;
    }

    private function isLocalStorageHost(?string $host): bool
    {
        if (! $host) {
            return false;
        }

        $appHost      = parse_url((string) config('app.url'), PHP_URL_HOST);
        $tenantDomain = config('multitenancy.resolvers.subdomain.domain');

        return $host === $appHost
            || ($tenantDomain && ($host === $tenantDomain || str_ends_with($host, '.'.$tenantDomain)));
    }
}
