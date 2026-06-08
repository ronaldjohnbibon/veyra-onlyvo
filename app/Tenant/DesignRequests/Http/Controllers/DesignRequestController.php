<?php

namespace App\Tenant\DesignRequests\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\AuditLogs\Services\TenantLogService;
use App\Tenant\DesignRequests\Http\Requests\DesignRequestActionRequest;
use App\Tenant\DesignRequests\Http\Requests\DesignRequestCommentRequest;
use App\Tenant\DesignRequests\Http\Requests\DesignRequestRequest;
use App\Tenant\DesignRequests\Http\Resources\DesignRequestResource;
use App\Tenant\DesignRequests\Models\DesignRequest;
use App\Tenant\DesignRequests\Services\DesignRequestService;
use App\Tenant\SystemSettings\Services\SystemSettingService;
use App\Tenant\SystemSettings\Services\TenantNotificationService;
use App\Tenant\Tenants\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DesignRequestController extends Controller
{
    public function __construct(
        private readonly DesignRequestService $service,
        private readonly SystemSettingService $settings,
        private readonly TenantNotificationService $notifications,
        private readonly TenantLogService $logs,
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
        $tenant        = Tenant::query()->find($user->tenant_id);
        $this->logs->recordModel('design_request.created', $designRequest, $user, $request, null, $designRequest->attributesToArray(), 'design_request', null, [
            'files_count' => $designRequest->files()->count(),
        ]);

        if ($tenant) {
            $this->notifications->sendDesignRequest($designRequest, $tenant);
            $this->logs->notification('design_request.notification_sent', [
                'tenant_id'    => $tenant->id,
                'entity_type'  => 'design_request',
                'entity_id'    => $designRequest->id,
                'entity_label' => $designRequest->title,
            ], $user, $request);
        }

        return $this->success(new DesignRequestResource($designRequest->load('events')), 'Design request submitted.', 201);
    }

    public function show(string $designRequest): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_design_requests_module')) {
            return $this->error('Design requests module is disabled.', 403);
        }

        $record = DesignRequest::query()
            ->with(['files', 'events'])
            ->whereKey($designRequest)
            ->first();

        if (! $record) {
            return $this->error('Design request not found.', 404);
        }

        return $this->success(new DesignRequestResource($record), 'Design request retrieved.');
    }

    public function comment(DesignRequestCommentRequest $request, string $designRequest): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_design_requests_module')) {
            return $this->error('Design requests module is disabled.', 403);
        }

        $user = Auth::user();
        abort_unless($user?->tenant_id, 403);

        $record = DesignRequest::query()
            ->with(['files', 'events'])
            ->whereKey($designRequest)
            ->first();

        if (! $record) {
            return $this->error('Design request not found.', 404);
        }

        $updated = $this->service->comment($record, $user, $request->validated('message'));
        $this->logs->activity('design_request.comment_added', [
            'tenant_id'    => $record->tenant_id,
            'entity_type'  => 'design_request',
            'entity_id'    => $record->id,
            'entity_label' => $record->title,
        ], $user, $request);

        return $this->success(
            new DesignRequestResource($updated),
            'Comment added.',
        );
    }

    public function action(DesignRequestActionRequest $request, string $designRequest): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_design_requests_module')) {
            return $this->error('Design requests module is disabled.', 403);
        }

        $user = Auth::user();
        abort_unless($user?->tenant_id, 403);

        $record = DesignRequest::query()
            ->with(['files', 'events'])
            ->whereKey($designRequest)
            ->first();

        if (! $record) {
            return $this->error('Design request not found.', 404);
        }

        $data = $request->validated();

        $previous = $record->attributesToArray();
        $updated  = $this->service->tenantAction($record, $user, (string) $data['action'], $data['message'] ?? null);
        $this->logs->recordModel('design_request.'.$data['action'], $updated, $user, $request, $previous, $updated->attributesToArray(), 'design_request');

        return $this->success(
            new DesignRequestResource($updated),
            'Request updated.',
        );
    }
}
