<?php

namespace App\Tenant\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\SystemSettings\Services\SystemSettingService;
use App\Tenant\Dashboard\Http\Requests\AnalyticsDashboardRequest;
use App\Tenant\Dashboard\Services\AnalyticsService;
use App\Tenant\SystemSettings\Services\TenantSystemSettingService;
use App\Tenant\Tenants\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $service,
        private readonly SystemSettingService $settings,
        private readonly TenantSystemSettingService $tenantSettings,
    ) {}

    public function index(AnalyticsDashboardRequest $request): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_analytics_module')) {
            return $this->error('Analytics module is disabled.', 403);
        }

        $tenant = $this->tenant();
        $this->service->pruneExpired(
            (string) $tenant->id,
            $this->tenantSettings->integer($tenant, 'analytics.retention_days', 365),
        );

        return $this->success($this->service->dashboard((string) $tenant->id, $request->validated()), 'Analytics retrieved.');
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
