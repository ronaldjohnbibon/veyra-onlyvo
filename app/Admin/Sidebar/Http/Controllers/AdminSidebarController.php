<?php

namespace App\Admin\Sidebar\Http\Controllers;

use App\Admin\AuditLogs\Services\AuditLogService;
use App\Admin\Sidebar\Http\Requests\SidebarRequest;
use App\Admin\Sidebar\Http\Resources\SidebarResource;
use App\Admin\Sidebar\Models\Sidebar;
use App\Admin\Sidebar\Services\SidebarService;
use App\Http\Controllers\Controller;
use App\Shared\Enums\UserType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminSidebarController extends Controller
{
    public function __construct(
        private readonly SidebarService $service,
        private readonly AuditLogService $auditLogs,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $sidebars = Sidebar::query()
            ->where('is_admin', true)
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
        $this->authorizeAdmin();

        $sidebar = $this->service->create(array_merge($request->validated(), [
            'is_admin'  => true,
            'tenant_id' => null,
        ]));
        $this->auditLogs->recordModel('sidebar.created', $sidebar, Auth::user(), $request, null, $sidebar->attributesToArray(), 'sidebar');

        return $this->success(new SidebarResource($sidebar), 'Sidebar created.', 201);
    }

    public function show(string $sidebar): JsonResponse
    {
        $this->authorizeAdmin();

        $record = Sidebar::query()->where('is_admin', true)->find($sidebar);

        if (! $record) {
            return $this->error('Sidebar not found.', 404);
        }

        return $this->success(new SidebarResource($record), 'Sidebar retrieved.');
    }

    public function update(SidebarRequest $request, string $sidebar): JsonResponse
    {
        $this->authorizeAdmin();

        $record = Sidebar::query()->where('is_admin', true)->find($sidebar);

        if (! $record) {
            return $this->error('Sidebar not found.', 404);
        }

        $previous = $record->attributesToArray();
        $updated  = $this->service->update($record, array_merge($request->validated(), [
            'is_admin'  => true,
            'tenant_id' => null,
        ]));
        $this->auditLogs->recordModel('sidebar.updated', $updated, Auth::user(), $request, $previous, $updated->attributesToArray(), 'sidebar');

        return $this->success(new SidebarResource($updated), 'Sidebar updated.');
    }

    public function destroy(string $sidebar): JsonResponse
    {
        $this->authorizeAdmin();

        $record = Sidebar::query()->where('is_admin', true)->find($sidebar);

        if (! $record) {
            return $this->error('Sidebar not found.', 404);
        }

        $previous = $record->attributesToArray();

        $record->delete();
        $this->auditLogs->recordModel('sidebar.deleted', $record, Auth::user(), $request, $previous, null, 'sidebar');

        return $this->success(null, 'Sidebar deleted.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->user_type === UserType::ADMIN, 403);
    }
}
