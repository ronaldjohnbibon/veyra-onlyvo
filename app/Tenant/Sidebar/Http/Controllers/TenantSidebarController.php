<?php

namespace App\Tenant\Sidebar\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\AuditLogs\Services\TenantLogService;
use App\Tenant\Sidebar\Http\Requests\SidebarRequest;
use App\Tenant\Sidebar\Http\Resources\SidebarResource;
use App\Tenant\Sidebar\Models\Sidebar;
use App\Tenant\Sidebar\Services\SidebarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantSidebarController extends Controller
{
    public function __construct(
        private readonly SidebarService $service,
        private readonly TenantLogService $logs,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $sidebars = Sidebar::query()
            ->where('is_admin', false)
            ->filter(['search' => $request->input('search')])
            ->latest()
            ->paginate(
                (int) $request->input('pageSize', 15),
                ['*'],
                'page',
                (int) $request->input('page', 1),
            );

        return $this->success(SidebarResource::collection($sidebars), 'Sidebars retrieved.');
    }

    public function store(SidebarRequest $request): JsonResponse
    {
        $sidebar = $this->service->create(array_merge($request->validated(), [
            'is_admin'  => false,
            'tenant_id' => $this->tenantId(),
        ]));
        $this->logs->recordModel('sidebar.created', $sidebar, Auth::user(), $request, null, $sidebar->attributesToArray(), 'sidebar');

        return $this->success(new SidebarResource($sidebar), 'Sidebar created.', 201);
    }

    public function show(string $sidebar): JsonResponse
    {
        $record = Sidebar::query()
            ->where('is_admin', false)
            ->find($sidebar);

        if (! $record) {
            return $this->error('Sidebar not found.', 404);
        }

        return $this->success(new SidebarResource($record), 'Sidebar retrieved.');
    }

    public function update(SidebarRequest $request, string $sidebar): JsonResponse
    {
        $record = Sidebar::query()
            ->where('is_admin', false)
            ->find($sidebar);

        if (! $record) {
            return $this->error('Sidebar not found.', 404);
        }

        $previous = $record->attributesToArray();
        $updated  = $this->service->update($record, array_merge($request->validated(), [
            'is_admin'  => false,
            'tenant_id' => $this->tenantId(),
        ]));
        $this->logs->recordModel('sidebar.updated', $updated, Auth::user(), $request, $previous, $updated->attributesToArray(), 'sidebar');

        return $this->success(new SidebarResource($updated), 'Sidebar updated.');
    }

    public function destroy(string $sidebar): JsonResponse
    {
        $record = Sidebar::query()
            ->where('is_admin', false)
            ->find($sidebar);

        if (! $record) {
            return $this->error('Sidebar not found.', 404);
        }

        $previous = $record->attributesToArray();
        $this->service->delete($record);
        $this->logs->recordModel('sidebar.deleted', $record, Auth::user(), request(), $previous, null, 'sidebar');

        return $this->success(null, 'Sidebar deleted.');
    }

    private function tenantId(): string
    {
        $tenantId = Auth::user()?->tenant_id;

        abort_unless($tenantId, 403);

        return $tenantId;
    }
}
