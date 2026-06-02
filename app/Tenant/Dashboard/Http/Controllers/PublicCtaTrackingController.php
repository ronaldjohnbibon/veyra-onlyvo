<?php

namespace App\Tenant\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\Dashboard\Http\Requests\CtaEventTrackingRequest;
use App\Tenant\Dashboard\Services\CtaTrackingService;
use Illuminate\Http\JsonResponse;
use Sprout\Contracts\Tenant as CurrentTenant;

class PublicCtaTrackingController extends Controller
{
    public function __construct(
        private readonly CtaTrackingService $service,
    ) {}

    public function store(CtaEventTrackingRequest $request, CurrentTenant $tenant): JsonResponse
    {
        $event = $this->service->record($request, (string) $tenant->getTenantKey(), $request->validated());

        if (! $event) {
            return $this->error('Published site not found.', 404);
        }

        return $this->success(['id' => $event->id], 'CTA event tracked.', 201);
    }
}
