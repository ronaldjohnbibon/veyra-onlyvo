<?php

namespace App\Modules\Templates\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Templates\Http\Requests\TemplateRequest;
use App\Modules\Templates\Http\Resources\TemplateResource;
use App\Modules\Templates\Http\Resources\WebsiteTypeResource;
use App\Modules\Templates\Models\Template;
use App\Modules\Templates\Models\WebsiteType;
use App\Modules\Templates\Services\TemplatePresetService;
use App\Modules\Templates\Services\TemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $templates = Template::query()
            ->with(['websiteType', 'templateDesign'])
            ->where('tenant_id', $this->tenantId())
            ->filter([
                'search'          => $request->input('search'),
                'website_type_id' => $request->input('website_type_id'),
            ])
            ->latest()
            ->paginate(
                (int) $request->input('pageSize', 15),
                ['*'],
                'page',
                (int) $request->input('page', 1),
            );

        return $this->success(TemplateResource::collection($templates), 'Templates retrieved.');
    }

    public function store(TemplateRequest $request, TemplateService $service): JsonResponse
    {
        $template = $service->create(array_merge($request->validated(), [
            'tenant_id' => $this->tenantId(),
        ]));

        return $this->success(new TemplateResource($template), 'Template saved.', 201);
    }

    public function show(string $template): JsonResponse
    {
        $record = $this->queryForTenant()->find($template);

        if (! $record) {
            return $this->error('Template not found.', 404);
        }

        return $this->success(new TemplateResource($record), 'Template retrieved.');
    }

    public function update(TemplateRequest $request, string $template, TemplateService $service): JsonResponse
    {
        $record = $this->queryForTenant()->find($template);

        if (! $record) {
            return $this->error('Template not found.', 404);
        }

        $updated = $service->update($record, array_merge($request->validated(), [
            'tenant_id' => $this->tenantId(),
        ]));

        return $this->success(new TemplateResource($updated), 'Template saved.');
    }

    public function destroy(string $template): JsonResponse
    {
        $record = $this->queryForTenant()->find($template);

        if (! $record) {
            return $this->error('Template not found.', 404);
        }

        $record->delete();

        return $this->success(null, 'Template deleted.');
    }

    public function websiteTypes(): JsonResponse
    {
        $types = WebsiteType::query()
            ->where('is_active', true)
            ->withCount(['templateDesigns', 'templates'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->success(WebsiteTypeResource::collection($types), 'Website types retrieved.');
    }

    public function designs(WebsiteType $websiteType, TemplatePresetService $presets): JsonResponse
    {
        if (! $websiteType->is_active) {
            return $this->error('Website type not found.', 404);
        }

        return $this->success($presets->all($websiteType->id), 'Template designs retrieved.');
    }

    public function presets(Request $request, TemplatePresetService $presets): JsonResponse
    {
        return $this->success($presets->all($request->input('website_type_id')), 'Template presets retrieved.');
    }

    public function published(string $template): JsonResponse
    {
        $record = $this->queryForTenant()
            ->where('status', 'published')
            ->find($template);

        if (! $record) {
            return $this->error('Published template not found.', 404);
        }

        return $this->success(new TemplateResource($record), 'Published template retrieved.');
    }

    private function queryForTenant()
    {
        return Template::query()
            ->with(['websiteType', 'templateDesign'])
            ->where('tenant_id', $this->tenantId());
    }

    private function tenantId(): string
    {
        $tenantId = Auth::user()?->tenant_id;

        abort_unless($tenantId, 403);

        return $tenantId;
    }
}
