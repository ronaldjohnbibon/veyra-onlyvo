<?php

namespace App\Modules\Templates\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Templates\Http\Requests\TemplateRequest;
use App\Modules\Templates\Http\Resources\TemplateResource;
use App\Modules\Templates\Http\Resources\WebsiteTypeResource;
use App\Modules\Templates\Models\Template;
use App\Modules\Templates\Models\WebsiteType;
use App\Modules\Templates\Services\TemplateCatalogService;
use App\Modules\Templates\Services\TemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TemplateController extends Controller
{
    public function __construct(
        private readonly TemplateService $service,
        private readonly TemplateCatalogService $catalog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $templates = Template::query()
            ->with('websiteType')
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

    public function store(TemplateRequest $request): JsonResponse
    {
        $template = $this->service->create(array_merge($request->validated(), [
            'tenant_id' => $this->tenantId(),
        ]));

        return $this->success(new TemplateResource($template), 'Template saved.', 201);
    }

    public function show(string $template): JsonResponse
    {
        $record = Template::query()
            ->with('websiteType')
            ->find($template);

        if (! $record) {
            return $this->error('Template not found.', 404);
        }

        return $this->success(new TemplateResource($record), 'Template retrieved.');
    }

    public function update(TemplateRequest $request, string $template): JsonResponse
    {
        $record = Template::query()
            ->with('websiteType')
            ->find($template);

        if (! $record) {
            return $this->error('Template not found.', 404);
        }

        $updated = $this->service->update($record, array_merge($request->validated(), [
            'tenant_id' => $this->tenantId(),
        ]));

        return $this->success(new TemplateResource($updated), 'Template saved.');
    }

    public function resetDefault(string $template): JsonResponse
    {
        $record = Template::query()
            ->with('websiteType')
            ->find($template);

        if (! $record) {
            return $this->error('Template not found.', 404);
        }

        $updated = $this->service->resetToDefault($record);

        return $this->success(new TemplateResource($updated), 'Template restored to default design.');
    }

    public function destroy(string $template): JsonResponse
    {
        $record = Template::query()
            ->with('websiteType')
            ->find($template);

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
            ->withCount('templates')
            ->withCount(['catalogItems as available_templates_count' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get();

        return $this->success(WebsiteTypeResource::collection($types), 'Website types retrieved.');
    }

    public function websiteTypeTemplates(WebsiteType $websiteType): JsonResponse
    {
        if (! $websiteType->is_active) {
            return $this->error('Website type not found.', 404);
        }

        return $this->success($this->catalog->forWebsiteType($websiteType), 'Website templates retrieved.');
    }

    public function published(string $template): JsonResponse
    {
        $record = Template::query()
            ->with('websiteType')
            ->where('status', 'published')
            ->find($template);

        if (! $record) {
            return $this->error('Published template not found.', 404);
        }

        return $this->success(new TemplateResource($record), 'Published template retrieved.');
    }

    private function tenantId(): string
    {
        $tenantId = Auth::user()?->tenant_id;

        abort_unless($tenantId, 403);

        return $tenantId;
    }
}
