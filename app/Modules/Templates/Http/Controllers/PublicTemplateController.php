<?php

namespace App\Modules\Templates\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Templates\Http\Resources\TemplateResource;
use App\Modules\Templates\Models\Template;
use Illuminate\Http\JsonResponse;
use Sprout\Contracts\Tenant as CurrentTenant;

class PublicTemplateController extends Controller
{
    public function show(CurrentTenant $tenant, string $slug): JsonResponse
    {
        $template = $this->publishedQuery((string) $tenant->getTenantKey())
            ->where('slug', $slug)
            ->first();

        if (! $template) {
            return $this->error('Published site not found.', 404);
        }

        return $this->success(new TemplateResource($template), 'Published site retrieved.');
    }

    public function defaultSite(CurrentTenant $tenant): JsonResponse
    {
        $template = $this->publishedQuery((string) $tenant->getTenantKey())
            ->where('is_default', true)
            ->first();

        if (! $template) {
            return $this->error('Published site not found.', 404);
        }

        return $this->success(new TemplateResource($template), 'Published site retrieved.');
    }

    private function publishedQuery(string $tenantId)
    {
        // Public visitors can only read enabled sections from published tenant sites.
        return Template::withoutTenantRestrictions(function () use ($tenantId) {
            return Template::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'published')
                ->with(['sections' => fn ($query) => $query->where('is_enabled', true)->orderBy('sort_order')]);
        });
    }
}
