<?php

namespace App\Modules\Analytics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Analytics\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AnalyticsController extends Controller
{
    public function index(Request $request, AnalyticsService $service): JsonResponse
    {
        $filters = $request->validate([
            'period'         => ['nullable', Rule::in(['today', 'last_7_days', 'last_30_days', 'custom'])],
            'from'           => ['nullable', 'date'],
            'to'             => ['nullable', 'date'],
            'pageSize'       => ['nullable', 'integer', 'min:5', 'max:50'],
            'top_pages_page' => ['nullable', 'integer', 'min:1'],
            'referrers_page' => ['nullable', 'integer', 'min:1'],
        ]);

        return $this->success($service->dashboard($this->tenantId(), $filters), 'Analytics retrieved.');
    }

    private function tenantId(): string
    {
        $tenantId = Auth::user()?->tenant_id;

        abort_unless($tenantId, 403);

        return $tenantId;
    }
}
