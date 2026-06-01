<?php

namespace App\Modules\Analytics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Analytics\Services\VisitorTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sprout\Contracts\Tenant as CurrentTenant;

class PublicVisitorTrackingController extends Controller
{
    public function store(Request $request, CurrentTenant $tenant, VisitorTrackingService $service): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => ['required', 'uuid'],
            'url'         => ['required', 'string', 'max:2000'],
            'referrer'    => ['nullable', 'string', 'max:2000'],
        ]);

        $visit = $service->record($request, (string) $tenant->getTenantKey(), $validated);

        if (! $visit) {
            return $this->error('Published site not found.', 404);
        }

        return $this->success(['id' => $visit->id], 'Visit tracked.', 201);
    }
}
