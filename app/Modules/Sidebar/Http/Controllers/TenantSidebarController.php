<?php

namespace App\Modules\Sidebar\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sidebar\Http\Requests\SidebarRequest;
use App\Modules\Sidebar\Http\Resources\SidebarResource;
use App\Modules\Sidebar\Models\Sidebar;
use App\Modules\Sidebar\Services\SidebarService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantSidebarController extends Controller
{
    public function __construct(
        private readonly SidebarService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId();

        $sidebars = Sidebar::query()
            ->where('is_admin', false)
            ->where('tenant_id', $tenantId)
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

        return $this->success(new SidebarResource($sidebar), 'Sidebar created.', 201);
    }

    public function show(string $sidebar): JsonResponse
    {
        $record = $this->queryForTenant()->find($sidebar);

        if (! $record) {
            return $this->error('Sidebar not found.', 404);
        }

        return $this->success(new SidebarResource($record), 'Sidebar retrieved.');
    }

    public function update(SidebarRequest $request, string $sidebar): JsonResponse
    {
        $record = $this->queryForTenant()->find($sidebar);

        if (! $record) {
            return $this->error('Sidebar not found.', 404);
        }

        $updated = $this->service->update($record, array_merge($request->validated(), [
            'is_admin'  => false,
            'tenant_id' => $this->tenantId(),
        ]));

        return $this->success(new SidebarResource($updated), 'Sidebar updated.');
    }

    public function destroy(string $sidebar): JsonResponse
    {
        $record = $this->queryForTenant()->find($sidebar);

        if (! $record) {
            return $this->error('Sidebar not found.', 404);
        }

        $record->delete();

        return $this->success(null, 'Sidebar deleted.');
    }

    private function queryForTenant(): Builder
    {
        return Sidebar::query()
            ->where('is_admin', false)
            ->where('tenant_id', $this->tenantId());
    }

    private function tenantId(): string
    {
        $tenantId = Auth::user()?->tenant_id;

        abort_unless($tenantId, 403);

        return $tenantId;
    }
}
