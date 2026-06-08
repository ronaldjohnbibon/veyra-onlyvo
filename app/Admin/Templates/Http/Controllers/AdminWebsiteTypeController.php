<?php

namespace App\Admin\Templates\Http\Controllers;

use App\Admin\AuditLogs\Services\AuditLogService;
use App\Admin\Templates\Http\Requests\WebsiteTypeRequest;
use App\Admin\Templates\Http\Resources\WebsiteTypeResource;
use App\Admin\Templates\Models\WebsiteType;
use App\Admin\Templates\Services\WebsiteTypeService;
use App\Http\Controllers\Controller;
use App\Shared\Enums\UserType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminWebsiteTypeController extends Controller
{
    public function __construct(
        private readonly WebsiteTypeService $service,
        private readonly AuditLogService $auditLogs,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $types = $this->service->paginate($request->only(['search', 'pageSize', 'page']));

        return $this->success(WebsiteTypeResource::collection($types), 'Website types retrieved.');
    }

    public function store(WebsiteTypeRequest $request): JsonResponse
    {
        $this->authorizeAdmin();

        $type = $this->service->create($request->validated());
        $this->auditLogs->recordModel('website_type.created', $type, Auth::user(), $request, null, $type->attributesToArray(), 'website_type');

        return $this->success(new WebsiteTypeResource($type), 'Website type created.', 201);
    }

    public function update(WebsiteTypeRequest $request, string $websiteType): JsonResponse
    {
        $this->authorizeAdmin();

        $type = WebsiteType::query()->find($websiteType);

        if (! $type) {
            return $this->error('Website type not found.', 404);
        }

        $previous = $type->attributesToArray();
        $updated  = $this->service->update($type, $request->validated());
        $this->auditLogs->recordModel('website_type.updated', $updated, Auth::user(), $request, $previous, $updated->attributesToArray(), 'website_type');

        return $this->success(new WebsiteTypeResource($updated), 'Website type updated.');
    }

    public function destroy(string $websiteType): JsonResponse
    {
        $this->authorizeAdmin();

        $type = WebsiteType::query()->find($websiteType);

        if (! $type) {
            return $this->error('Website type not found.', 404);
        }

        $previous = $type->attributesToArray();

        if (! $this->service->delete($type)) {
            return $this->error('Website type is still in use.', 422);
        }

        $this->auditLogs->recordModel('website_type.deleted', $type, Auth::user(), request(), $previous, null, 'website_type');

        return $this->success(null, 'Website type deleted.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->user_type === UserType::ADMIN, 403);
    }
}
