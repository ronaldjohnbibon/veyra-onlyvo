<?php

namespace App\Modules\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Enums\UserType;
use App\Modules\Tenant\Http\Requests\TenantRequest;
use App\Modules\Tenant\Http\Resources\TenantResource;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminTenantController extends Controller
{
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

    public function store(TenantRequest $request, TenantService $service): JsonResponse
    {
        $this->authorizeAdmin();

        $tenant = $service->create($request->validated());

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

    public function update(TenantRequest $request, string $tenant, TenantService $service): JsonResponse
    {
        $this->authorizeAdmin();

        $record = Tenant::query()->find($tenant);

        if (! $record) {
            return $this->error('Tenant not found.', 404);
        }

        $updated = $service->update($record, $request->validated())->load('owner')->loadCount(['users', 'templates']);

        return $this->success(new TenantResource($updated), 'Tenant updated.');
    }

    public function deactivate(string $tenant, TenantService $service): JsonResponse
    {
        $this->authorizeAdmin();

        return $this->statusResponse($tenant, 'inactive', 'Tenant deactivated.', $service);
    }

    public function reactivate(string $tenant, TenantService $service): JsonResponse
    {
        $this->authorizeAdmin();

        return $this->statusResponse($tenant, 'active', 'Tenant reactivated.', $service);
    }

    public function destroy(string $tenant, TenantService $service): JsonResponse
    {
        $this->authorizeAdmin();

        $record = Tenant::query()->find($tenant);

        if (! $record) {
            return $this->error('Tenant not found.', 404);
        }

        $service->delete($record);

        return $this->success(null, 'Tenant deleted.');
    }

    private function statusResponse(string $tenant, string $status, string $message, TenantService $service): JsonResponse
    {
        $record = Tenant::query()->find($tenant);

        if (! $record) {
            return $this->error('Tenant not found.', 404);
        }

        // Status updates keep tenant history while toggling access.
        $updated = $service->setStatus($record, $status)->load('owner')->loadCount(['users', 'templates']);

        return $this->success(new TenantResource($updated), $message);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->user_type === UserType::ADMIN, 403);
    }
}
