<?php

namespace App\Admin\Tenants\Http\Controllers;

use App\Admin\Tenants\Http\Requests\TenantRequest;
use App\Admin\Tenants\Http\Resources\TenantResource;
use App\Admin\Tenants\Models\Tenant;
use App\Admin\Tenants\Services\TenantService;
use App\Admin\Tenants\Services\TenantWorkspaceService;
use App\Http\Controllers\Controller;
use App\Shared\Enums\UserType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminTenantController extends Controller
{
    public function __construct(
        private readonly TenantService $service,
        private readonly TenantWorkspaceService $workspaceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $sort = in_array($request->input('sort'), ['name', 'subdomain', 'timezone', 'status', 'created_at'], true)
            ? $request->input('sort')
            : 'created_at';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $tenants = Tenant::query()
            ->when($request->input('search'), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('subdomain', 'like', '%'.$search.'%')
                        ->orWhere('timezone', 'like', '%'.$search.'%');
                });
            })
            ->when($request->input('status'), fn ($query, string $status) => $query->where('status', $status))
            ->with('owner')
            ->withCount(['users', 'templates'])
            ->orderBy($sort, $direction)
            ->paginate(
                (int) $request->input('pageSize', 15),
                ['*'],
                'page',
                (int) $request->input('page', 1),
            );

        return $this->success(TenantResource::collection($tenants), 'Tenants retrieved.');
    }

    public function store(TenantRequest $request): JsonResponse
    {
        $this->authorizeAdmin();

        $tenant = $this->service->create($request->validated());

        return $this->success(new TenantResource($tenant->load('owner')), 'Tenant created.', 201);
    }

    public function show(string $tenant): JsonResponse
    {
        $this->authorizeAdmin();

        $record = Tenant::query()->with('owner')->withCount(['users', 'templates'])->find($tenant);

        if (! $record) {
            return $this->error('Tenant not found.', 404);
        }

        return $this->success(new TenantResource($record), 'Tenant retrieved.');
    }

    public function workspace(Request $request, string $tenant): JsonResponse
    {
        $this->authorizeAdmin();

        $record = Tenant::query()->with('owner')->withCount(['users', 'templates'])->find($tenant);

        if (! $record) {
            return $this->error('Tenant not found.', 404);
        }

        return $this->success($this->workspaceService->workspace($record, $request), 'Tenant workspace retrieved.');
    }

    public function update(TenantRequest $request, string $tenant): JsonResponse
    {
        $this->authorizeAdmin();

        $record = Tenant::query()->find($tenant);

        if (! $record) {
            return $this->error('Tenant not found.', 404);
        }

        $updated = $this->service->update($record, $request->validated())->load('owner')->loadCount(['users', 'templates']);

        return $this->success(new TenantResource($updated), 'Tenant updated.');
    }

    public function deactivate(string $tenant): JsonResponse
    {
        $this->authorizeAdmin();

        return $this->statusResponse($tenant, 'inactive', 'Tenant deactivated.');
    }

    public function reactivate(string $tenant): JsonResponse
    {
        $this->authorizeAdmin();

        return $this->statusResponse($tenant, 'active', 'Tenant reactivated.');
    }

    public function destroy(string $tenant): JsonResponse
    {
        $this->authorizeAdmin();

        $record = Tenant::query()->find($tenant);

        if (! $record) {
            return $this->error('Tenant not found.', 404);
        }

        $this->service->delete($record);

        return $this->success(null, 'Tenant deleted.');
    }

    private function statusResponse(string $tenant, string $status, string $message): JsonResponse
    {
        $record = Tenant::query()->find($tenant);

        if (! $record) {
            return $this->error('Tenant not found.', 404);
        }

        // Status updates keep tenant history while toggling access.
        $updated = $this->service->setStatus($record, $status)->load('owner')->loadCount(['users', 'templates']);

        return $this->success(new TenantResource($updated), $message);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->user_type === UserType::ADMIN, 403);
    }
}
