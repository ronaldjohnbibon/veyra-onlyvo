<?php

namespace App\Tenant\SystemSettings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\AuditLogs\Services\TenantLogService;
use App\Tenant\SystemSettings\Http\Requests\TenantSystemSettingBulkRequest;
use App\Tenant\SystemSettings\Http\Requests\TenantSystemSettingHistoryIndexRequest;
use App\Tenant\SystemSettings\Http\Requests\TenantSystemSettingImageUploadRequest;
use App\Tenant\SystemSettings\Http\Resources\TenantSystemSettingHistoryResource;
use App\Tenant\SystemSettings\Services\TenantSystemSettingService;
use App\Tenant\Tenants\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TenantSystemSettingController extends Controller
{
    public function __construct(
        private readonly TenantSystemSettingService $service,
        private readonly TenantLogService $logs,
    ) {}

    public function index(): JsonResponse
    {
        $tenant  = $this->tenant();
        $history = $this->historyPayload($tenant);

        return $this->success([
            'groups'  => $this->service->groups($tenant),
            'values'  => $this->service->values($tenant),
            'history' => $history,
        ], 'Tenant system settings retrieved.');
    }

    public function history(TenantSystemSettingHistoryIndexRequest $request): JsonResponse
    {
        return $this->success($this->historyPayload($this->tenant(), $request->validated()), 'Tenant system settings history retrieved.');
    }

    public function updateBulk(TenantSystemSettingBulkRequest $request): JsonResponse
    {
        $tenant  = $this->service->upsertGrouped($this->tenant(), $request->validated('settings'), Auth::user());
        $history = $this->historyPayload($tenant);
        $this->logs->system('tenant_system_settings.updated', [
            'tenant_id'    => $tenant->id,
            'entity_type'  => 'tenant_system_setting',
            'entity_id'    => $tenant->id,
            'entity_label' => $tenant->name,
            'new_value'    => $request->validated('settings'),
        ], Auth::user(), $request);

        return $this->success([
            'groups'  => $this->service->groups($tenant),
            'values'  => $this->service->values($tenant),
            'history' => $history,
        ], 'Tenant system settings updated.');
    }

    public function uploadImage(TenantSystemSettingImageUploadRequest $request): JsonResponse
    {
        $tenant = $this->tenant();
        $image  = $request->file('image');

        abort_unless($image, 422);

        $path = $image->storePublicly("tenant-system-settings/{$tenant->id}", 'public');
        $this->logs->fileUpload('tenant_system_setting.image_uploaded', [
            'tenant_id'    => $tenant->id,
            'entity_type'  => 'tenant_system_setting',
            'entity_id'    => (string) $request->validated('key'),
            'entity_label' => (string) $request->validated('key'),
            'metadata'     => [
                'path'          => $path,
                'original_name' => $image->getClientOriginalName(),
                'size'          => $image->getSize(),
                'mime_type'     => $image->getMimeType(),
            ],
        ], Auth::user(), $request);

        return $this->success([
            'key'  => $request->validated('key'),
            'url'  => '/storage/'.$path,
            'path' => $path,
        ], 'Image uploaded.', 201);
    }

    private function tenant(): Tenant
    {
        $tenantId = Auth::user()?->tenant_id;

        abort_unless($tenantId, 403);

        $tenant = Tenant::query()->find($tenantId);

        abort_unless($tenant, 404);

        return $tenant;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function historyPayload(Tenant $tenant, array $filters = []): array
    {
        $history = $this->service->history($tenant, $filters);

        return [
            'data'       => TenantSystemSettingHistoryResource::collection($history['data'])->resolve(),
            'pagination' => $history['pagination'],
        ];
    }
}
