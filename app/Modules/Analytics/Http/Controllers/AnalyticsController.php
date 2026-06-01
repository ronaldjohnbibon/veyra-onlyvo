<?php

namespace App\Modules\Analytics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Analytics\Http\Requests\AnalyticsDashboardRequest;
use App\Modules\Analytics\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    public function index(AnalyticsDashboardRequest $request, AnalyticsService $service): JsonResponse
    {
        return $this->success($service->dashboard($this->tenantId(), $request->validated()), 'Analytics retrieved.');
    }

    private function tenantId(): string
    {
        $tenantId = Auth::user()?->tenant_id;

        abort_unless($tenantId, 403);

        return $tenantId;
    }
}
