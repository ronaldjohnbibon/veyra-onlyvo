<?php

namespace App\Modules\Analytics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Analytics\Http\Requests\CtaEventTrackingRequest;
use App\Modules\Analytics\Services\CtaTrackingService;
use Illuminate\Http\JsonResponse;
use Sprout\Contracts\Tenant as CurrentTenant;

class PublicCtaTrackingController extends Controller
{
    public function store(CtaEventTrackingRequest $request, CurrentTenant $tenant, CtaTrackingService $service): JsonResponse
    {
        $event = $service->record($request, (string) $tenant->getTenantKey(), $request->validated());

        if (! $event) {
            return $this->error('Published site not found.', 404);
        }

        return $this->success(['id' => $event->id], 'CTA event tracked.', 201);
    }
}
