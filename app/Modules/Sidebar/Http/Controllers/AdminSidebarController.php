<?php

namespace App\Modules\Sidebar\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Enums\UserType;
use App\Modules\Sidebar\Http\Requests\SidebarRequest;
use App\Modules\Sidebar\Http\Resources\SidebarResource;
use App\Modules\Sidebar\Models\Sidebar;
use App\Modules\Sidebar\Services\SidebarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminSidebarController extends Controller
{
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

    public function store(SidebarRequest $request, SidebarService $service): JsonResponse
    {
        $this->authorizeAdmin();

        $sidebar = $service->create(array_merge($request->validated(), [
            'is_admin'  => true,
            'tenant_id' => null,
        ]));

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

    public function update(SidebarRequest $request, string $sidebar, SidebarService $service): JsonResponse
    {
        $this->authorizeAdmin();

        $record = Sidebar::query()->where('is_admin', true)->find($sidebar);

        if (! $record) {
            return $this->error('Sidebar not found.', 404);
        }

        $updated = $service->update($record, array_merge($request->validated(), [
            'is_admin'  => true,
            'tenant_id' => null,
        ]));

        return $this->success(new SidebarResource($updated), 'Sidebar updated.');
    }

    public function destroy(string $sidebar): JsonResponse
    {
        $this->authorizeAdmin();

        $record = Sidebar::query()->where('is_admin', true)->find($sidebar);

        if (! $record) {
            return $this->error('Sidebar not found.', 404);
        }

        $record->delete();

        return $this->success(null, 'Sidebar deleted.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->user_type === UserType::ADMIN, 403);
    }
}
