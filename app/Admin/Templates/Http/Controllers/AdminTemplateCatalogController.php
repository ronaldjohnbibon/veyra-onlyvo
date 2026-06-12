<?php

namespace App\Admin\Templates\Http\Controllers;

use App\Admin\Templates\Http\Requests\TemplateCatalogItemRequest;
use App\Admin\Templates\Http\Resources\TemplateCatalogItemResource;
use App\Admin\Templates\Models\TemplateCatalogItem;
use App\Admin\Templates\Models\TemplateCatalogItemVersion;
use App\Admin\Templates\Services\TemplateCatalogMaintenanceService;
use App\Http\Controllers\Controller;
use App\Shared\Enums\UserType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AdminTemplateCatalogController extends Controller
{
    public function __construct(
        private readonly TemplateCatalogMaintenanceService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $items = TemplateCatalogItem::query()
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

        return $this->success(TemplateCatalogItemResource::collection($items), 'Template catalog retrieved.');
    }

    public function store(TemplateCatalogItemRequest $request): JsonResponse
    {
        $this->authorizeAdmin();

        $item = $this->service->create($request->validated(), Auth::user());

        return $this->success(new TemplateCatalogItemResource($item->load('websiteType')), 'Template catalog item created.', 201);
    }

    public function show(string $templateCatalogItem): JsonResponse
    {
        $this->authorizeAdmin();

        $item = TemplateCatalogItem::query()->with('websiteType')->find($templateCatalogItem);

        if (! $item) {
            return $this->error('Template catalog item not found.', 404);
        }

        return $this->success(new TemplateCatalogItemResource($item), 'Template catalog item retrieved.');
    }

    public function update(TemplateCatalogItemRequest $request, string $templateCatalogItem): JsonResponse
    {
        $this->authorizeAdmin();

        $item = TemplateCatalogItem::query()->find($templateCatalogItem);

        if (! $item) {
            return $this->error('Template catalog item not found.', 404);
        }

        $item = $this->service->update($item, $request->validated(), Auth::user());

        return $this->success(new TemplateCatalogItemResource($item), 'Template catalog item updated.');
    }

    public function cloneItem(Request $request, string $templateCatalogItem): JsonResponse
    {
        $this->authorizeAdmin();

        $item = $this->catalogItem($templateCatalogItem);

        if (! $item) {
            return $this->error('Template catalog item not found.', 404);
        }

        if ($request->has('key')) {
            $request->merge(['key' => Str::slug((string) $request->input('key')) ?: null]);
        }

        $payload = $request->validate([
            'name'        => ['nullable', 'string', 'max:150'],
            'key'         => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string'],
            'changelog'   => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->success(new TemplateCatalogItemResource($this->service->cloneItem($item, $payload, Auth::user())), 'Template cloned.', 201);
    }

    public function publish(string $templateCatalogItem): JsonResponse
    {
        $this->authorizeAdmin();

        $item = $this->catalogItem($templateCatalogItem);

        if (! $item) {
            return $this->error('Template catalog item not found.', 404);
        }

        return $this->success(new TemplateCatalogItemResource($this->service->publish($item, Auth::user())), 'Template published.');
    }

    public function unpublish(string $templateCatalogItem): JsonResponse
    {
        $this->authorizeAdmin();

        $item = $this->catalogItem($templateCatalogItem);

        if (! $item) {
            return $this->error('Template catalog item not found.', 404);
        }

        return $this->success(new TemplateCatalogItemResource($this->service->unpublish($item, Auth::user())), 'Template unpublished.');
    }

    public function versions(string $templateCatalogItem): JsonResponse
    {
        $this->authorizeAdmin();

        $item = $this->catalogItem($templateCatalogItem);

        if (! $item) {
            return $this->error('Template catalog item not found.', 404);
        }

        return $this->success($this->service->versions($item), 'Template versions retrieved.');
    }

    public function rollback(string $templateCatalogItem, string $version): JsonResponse
    {
        $this->authorizeAdmin();

        $item = $this->catalogItem($templateCatalogItem);

        if (! $item) {
            return $this->error('Template catalog item not found.', 404);
        }

        $record = TemplateCatalogItemVersion::query()->find($version);

        if (! $record) {
            return $this->error('Template version not found.', 404);
        }

        return $this->success(new TemplateCatalogItemResource($this->service->rollback($item, $record, Auth::user())), 'Template rolled back.');
    }

    public function exportSchema(string $templateCatalogItem): JsonResponse
    {
        $this->authorizeAdmin();

        $item = $this->catalogItem($templateCatalogItem);

        if (! $item) {
            return $this->error('Template catalog item not found.', 404);
        }

        return $this->success($this->service->export($item), 'Template schema exported.');
    }

    public function importSchema(Request $request, string $templateCatalogItem): JsonResponse
    {
        $this->authorizeAdmin();

        $item = $this->catalogItem($templateCatalogItem);

        if (! $item) {
            return $this->error('Template catalog item not found.', 404);
        }

        $payload = $request->validate([
            'field_schema'    => ['required', 'array'],
            'default_content' => ['nullable', 'array'],
            'changelog'       => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->success(new TemplateCatalogItemResource($this->service->importSchema($item, $payload, Auth::user())), 'Template schema imported.');
    }

    public function validateTemplate(string $templateCatalogItem): JsonResponse
    {
        $this->authorizeAdmin();

        $item = $this->catalogItem($templateCatalogItem);

        if (! $item) {
            return $this->error('Template catalog item not found.', 404);
        }

        return $this->success([
            'validation'    => $this->service->validation($item),
            'qa_checklists' => $this->service->qaChecklist($item),
        ], 'Template validation completed.');
    }

    public function destroy(string $templateCatalogItem): JsonResponse
    {
        $this->authorizeAdmin();

        $item = TemplateCatalogItem::query()->find($templateCatalogItem);

        if (! $item) {
            return $this->error('Template catalog item not found.', 404);
        }

        $this->service->delete($item, Auth::user());

        return $this->success(null, 'Template catalog item deleted.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->user_type === UserType::ADMIN, 403);
    }

    private function catalogItem(string $id): ?TemplateCatalogItem
    {
        return TemplateCatalogItem::query()->with('websiteType')->find($id);
    }
}
