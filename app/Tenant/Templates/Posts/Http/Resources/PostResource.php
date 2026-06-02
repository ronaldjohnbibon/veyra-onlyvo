<?php

namespace App\Tenant\Templates\Posts\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'template_id'    => $this->template_id,
            'title'          => $this->title,
            'slug'           => $this->slug,
            'content'        => $this->content,
            'excerpt'        => str(strip_tags((string) $this->content))->limit(180)->toString(),
            'featured_image' => $this->featuredImageUrl($request),
            'status'         => $this->status,
            'published_at'   => $this->published_at,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }

    private function featuredImageUrl(Request $request): ?string
    {
        $value = $this->featured_image;

        if (! $value) {
            return null;
        }

        $parts = parse_url($value) ?: [];
        $path  = $parts['path'] ?? $value;

        if (($parts['scheme'] ?? null) && ! $this->isLocalStorageHost($parts['host'] ?? null, $request)) {
            return $value;
        }

        // Return public-disk images as same-origin URLs for tenant public pages.
        if (str_starts_with($path, '/storage/')) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            return '/'.$path;
        }

        if (! parse_url($value, PHP_URL_SCHEME)) {
            return '/storage/'.ltrim($value, '/');
        }

        return $value;
    }

    private function isLocalStorageHost(?string $host, Request $request): bool
    {
        if (! $host) {
            return false;
        }

        $appHost      = parse_url((string) config('app.url'), PHP_URL_HOST);
        $tenantDomain = config('multitenancy.resolvers.subdomain.domain');

        return $host === $request->getHost()
            || $host === $appHost
            || ($tenantDomain && ($host === $tenantDomain || str_ends_with($host, '.'.$tenantDomain)));
    }
}
