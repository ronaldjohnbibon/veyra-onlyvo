<?php

namespace App\Tenant\TrackingLogs\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\SystemSettings\Services\SystemSettingService;
use App\Tenant\TrackingLogs\Http\Requests\TrackingLogIndexRequest;
use App\Tenant\TrackingLogs\Services\TrackingLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TrackingLogController extends Controller
{
    public function __construct(
        private readonly TrackingLogService $service,
        private readonly SystemSettingService $settings,
    ) {}

    public function index(TrackingLogIndexRequest $request): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_tracking_logs')) {
            return $this->error('Tracking logs are disabled.', 403);
        }

        return $this->success($this->service->index($this->tenantId(), $request->validated()), 'Tracking logs retrieved.');
    }

    private function tenantId(): string
    {
        $tenantId = Auth::user()?->tenant_id;

        abort_unless($tenantId, 403);

        return $tenantId;
    }
}
