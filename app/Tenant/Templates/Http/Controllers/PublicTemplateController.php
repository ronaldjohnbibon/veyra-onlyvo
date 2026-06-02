<?php

namespace App\Tenant\Templates\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\SystemSettings\Services\SystemSettingService;
use App\Tenant\Templates\Http\Resources\TemplateResource;
use App\Tenant\Templates\Models\Template;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class PublicTemplateController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    public function show(string $slug): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_templates_module')) {
            return $this->error('Templates module is disabled.', 403);
        }

        $template = $this->publishedQuery()
            ->where('slug', $slug)
            ->first();

        if (! $template) {
            return $this->error('Published site not found.', 404);
        }

        return $this->success(new TemplateResource($template), 'Published site retrieved.');
    }

    public function defaultSite(): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_templates_module')) {
            return $this->error('Templates module is disabled.', 403);
        }

        $template = $this->publishedQuery()
            ->where('is_default', true)
            ->first();

        if (! $template) {
            return $this->error('Published site not found.', 404);
        }

        return $this->success(new TemplateResource($template), 'Published site retrieved.');
    }

    private function publishedQuery(): Builder
    {
        return Template::query()
            ->with('websiteType')
            ->where('status', 'published');
    }
}
