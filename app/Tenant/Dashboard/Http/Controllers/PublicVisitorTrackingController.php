<?php

namespace App\Tenant\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\Dashboard\Http\Requests\VisitorTrackingRequest;
use App\Tenant\Dashboard\Services\VisitorTrackingService;
use Illuminate\Http\JsonResponse;
use Sprout\Contracts\Tenant as CurrentTenant;

class PublicVisitorTrackingController extends Controller
{
    public function __construct(
        private readonly VisitorTrackingService $service,
    ) {}

    public function store(VisitorTrackingRequest $request, CurrentTenant $tenant): JsonResponse
    {
        $visit = $this->service->record($request, (string) $tenant->getTenantKey(), $request->validated());

        if (! $visit) {
            return $this->error('Published site not found.', 404);
        }

        return $this->success(['id' => $visit->id], 'Visit tracked.', 201);
    }
}
