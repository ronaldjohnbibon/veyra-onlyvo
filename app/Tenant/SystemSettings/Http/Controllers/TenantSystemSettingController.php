<?php

namespace App\Tenant\SystemSettings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\SystemSettings\Http\Requests\TenantSystemSettingBulkRequest;
use App\Tenant\SystemSettings\Http\Requests\TenantSystemSettingImageUploadRequest;
use App\Tenant\SystemSettings\Services\TenantSystemSettingService;
use App\Tenant\Tenants\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TenantSystemSettingController extends Controller
{
    public function __construct(
        private readonly TenantSystemSettingService $service,
    ) {}

    public function index(): JsonResponse
    {
        $tenant = $this->tenant();

        return $this->success([
            'groups' => $this->service->groups($tenant),
            'values' => $this->service->values($tenant),
        ], 'Tenant system settings retrieved.');
    }

    public function updateBulk(TenantSystemSettingBulkRequest $request): JsonResponse
    {
        $tenant = $this->service->upsertGrouped($this->tenant(), $request->validated('settings'));

        return $this->success([
            'groups' => $this->service->groups($tenant),
            'values' => $this->service->values($tenant),
        ], 'Tenant system settings updated.');
    }

    public function uploadImage(TenantSystemSettingImageUploadRequest $request): JsonResponse
    {
        $tenant = $this->tenant();
        $image  = $request->file('image');

        abort_unless($image, 422);

        $path = $image->storePublicly("tenant-system-settings/{$tenant->id}", 'public');

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
}
