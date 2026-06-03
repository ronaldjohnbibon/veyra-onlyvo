<?php

namespace App\Tenant\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\SystemSettings\Services\SystemSettingService;
use App\Tenant\Dashboard\Http\Requests\VisitorTrackingRequest;
use App\Tenant\Dashboard\Services\VisitorTrackingService;
use App\Tenant\SystemSettings\Services\TenantSystemSettingService;
use Illuminate\Http\JsonResponse;
use Sprout\Contracts\Tenant as CurrentTenant;

class PublicVisitorTrackingController extends Controller
{
    public function __construct(
        private readonly VisitorTrackingService $service,
        private readonly SystemSettingService $settings,
        private readonly TenantSystemSettingService $tenantSettings,
    ) {}

    public function store(VisitorTrackingRequest $request, CurrentTenant $tenant): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_analytics_module')) {
            return $this->success(null, 'Analytics module disabled.');
        }

        if (! $this->settings->boolean('analytics.enable_visitor_tracking', true)) {
            return $this->success(null, 'Visitor tracking disabled.');
        }

        if (! $this->tenantSettings->boolean($tenant, 'analytics.enable_visitor_tracking', true)) {
            return $this->success(null, 'Tenant visitor tracking disabled.');
        }

        $visit = $this->service->record($request, (string) $tenant->getTenantKey(), $request->validated());

        if (! $visit) {
            return $this->error('Published site not found.', 404);
        }

        return $this->success(['id' => $visit->id], 'Visit tracked.', 201);
    }
}
