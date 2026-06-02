<?php

namespace App\Tenant\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\Dashboard\Http\Requests\AnalyticsDashboardRequest;
use App\Tenant\Dashboard\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $service,
    ) {}

    public function index(AnalyticsDashboardRequest $request): JsonResponse
    {
        return $this->success($this->service->dashboard($this->tenantId(), $request->validated()), 'Analytics retrieved.');
    }

    private function tenantId(): string
    {
        $tenantId = Auth::user()?->tenant_id;

        abort_unless($tenantId, 403);

        return $tenantId;
    }
}
