<?php

namespace App\Modules\Analytics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Analytics\Http\Requests\VisitorTrackingRequest;
use App\Modules\Analytics\Services\VisitorTrackingService;
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
