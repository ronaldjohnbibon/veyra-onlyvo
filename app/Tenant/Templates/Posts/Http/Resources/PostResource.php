<?php

namespace App\Tenant\Templates\Posts\Http\Resources;

use App\Tenant\SystemSettings\Services\TenantSystemSettingService;
use App\Tenant\Tenants\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $template = $this->relationLoaded('template') ? $this->template : null;
        $tenant   = $template?->relationLoaded('tenant') ? $template->tenant : null;

        return [
            'id'               => $this->id,
            'template_id'      => $this->template_id,
            'site_slug'        => $template?->slug,
            'site_name'        => $template?->business_name,
            'title'            => $this->title,
            'slug'             => $this->slug,
            'content'          => $this->content,
            'excerpt'          => $this->excerpt ?: str(strip_tags((string) $this->content))->limit(180)->toString(),
            'featured_image'   => $this->featuredImageUrl($request),
            'seo_title'        => $this->seo_title,
            'meta_description' => $this->meta_description,
            'tags'             => $this->tags ?? [],
            'status'           => $this->status,
            'tenant_settings'  => $tenant ? $this->tenantSettings($request, $tenant) : [],
            'published_at'     => $this->published_at,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tenantSettings(Request $request, Tenant $tenant): array
    {
        $settings = app(TenantSystemSettingService::class);

        return str_starts_with((string) $request->route()?->getName(), 'public.')
            ? $settings->publicValues($tenant)
            : $settings->templateValues($tenant);
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

        if (str_starts_with($path, '/')) {
            return $path;
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
