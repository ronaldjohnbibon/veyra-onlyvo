<?php

namespace App\Tenant\DesignRequests\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\SystemSettings\Services\SystemSettingService;
use App\Tenant\DesignRequests\Http\Requests\DesignRequestRequest;
use App\Tenant\DesignRequests\Http\Resources\DesignRequestResource;
use App\Tenant\DesignRequests\Models\DesignRequest;
use App\Tenant\DesignRequests\Services\DesignRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DesignRequestController extends Controller
{
    public function __construct(
        private readonly DesignRequestService $service,
        private readonly SystemSettingService $settings,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_design_requests_module')) {
            return $this->error('Design requests module is disabled.', 403);
        }

        $sorts = [
            'created_at' => 'created_at',
            'status'     => 'status',
            'title'      => 'title',
        ];
        $sort       = (string) $request->input('sort', 'created_at');
        $sortColumn = $sorts[$sort] ?? 'created_at';
        $direction  = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $requests = DesignRequest::query()
            ->with('files')
            ->filter([
                'search' => $request->input('search'),
                'status' => $request->input('status'),
            ])
            ->orderBy($sortColumn, $direction)
            ->orderBy('title')
            ->paginate(
                (int) $request->input('pageSize', 15),
                ['*'],
                'page',
                (int) $request->input('page', 1),
            );

        return $this->success(DesignRequestResource::collection($requests), 'Design requests retrieved.');
    }

    public function store(DesignRequestRequest $request): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_design_requests_module')) {
            return $this->error('Design requests module is disabled.', 403);
        }

        $user = Auth::user();

        abort_unless($user?->tenant_id, 403);

        $designRequest = $this->service->create($user, $request->validated());

        return $this->success(new DesignRequestResource($designRequest), 'Design request submitted.', 201);
    }

    public function show(string $designRequest): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_design_requests_module')) {
            return $this->error('Design requests module is disabled.', 403);
        }

        $record = DesignRequest::query()
            ->with('files')
            ->whereKey($designRequest)
            ->first();

        if (! $record) {
            return $this->error('Design request not found.', 404);
        }

        return $this->success(new DesignRequestResource($record), 'Design request retrieved.');
    }
}
