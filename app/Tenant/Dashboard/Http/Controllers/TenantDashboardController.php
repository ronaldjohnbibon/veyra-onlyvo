<?php

namespace App\Tenant\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\Dashboard\Services\TenantDashboardService;
use App\Tenant\Tenants\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantDashboardController extends Controller
{
    public function __construct(
        private readonly TenantDashboardService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->success(
            $this->service->dashboard($this->tenant(), rtrim($request->getSchemeAndHttpHost(), '/')),
            'Dashboard retrieved.',
        );
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
