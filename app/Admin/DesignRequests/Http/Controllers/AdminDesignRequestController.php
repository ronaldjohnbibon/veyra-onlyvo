<?php

namespace App\Admin\DesignRequests\Http\Controllers;

use App\Admin\DesignRequests\Http\Requests\AdminDesignRequestCommentRequest;
use App\Admin\DesignRequests\Http\Requests\AdminDesignRequestConversionRequest;
use App\Admin\DesignRequests\Http\Requests\AdminDesignRequestLinkRequest;
use App\Admin\DesignRequests\Http\Requests\AdminDesignRequestStatusRequest;
use App\Admin\DesignRequests\Http\Resources\DesignRequestResource;
use App\Admin\DesignRequests\Models\DesignRequest;
use App\Admin\DesignRequests\Services\DesignRequestService;
use App\Admin\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Shared\Enums\UserType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminDesignRequestController extends Controller
{
    public function __construct(
        private readonly DesignRequestService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $sorts = [
            'created_at' => 'created_at',
            'status'     => 'status',
            'priority'   => 'priority',
            'due_at'     => 'due_at',
            'title'      => 'title',
        ];
        $sort       = (string) $request->input('sort', 'created_at');
        $sortColumn = $sorts[$sort] ?? 'created_at';
        $direction  = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $requests = DesignRequest::query()
            ->with(['files', 'tenant', 'requester', 'assignee', 'linkedTemplate'])
            ->filter([
                'search'      => $request->input('search'),
                'status'      => $request->input('status'),
                'tenant_id'   => $request->input('tenant_id'),
                'assigned_to' => $request->input('assigned_to'),
                'priority'    => $request->input('priority'),
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

    public function board(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $requests = DesignRequest::query()
            ->with(['files', 'tenant', 'requester', 'assignee'])
            ->filter([
                'search'      => $request->input('search'),
                'tenant_id'   => $request->input('tenant_id'),
                'assigned_to' => $request->input('assigned_to'),
                'priority'    => $request->input('priority'),
            ])
            ->whereNot('status', 'completed')
            ->orderByRaw("case priority when 'urgent' then 1 when 'high' then 2 when 'normal' then 3 else 4 end")
            ->orderBy('due_at')
            ->latest()
            ->get()
            ->groupBy('status')
            ->map(fn ($items) => DesignRequestResource::collection($items)->resolve())
            ->all();

        return $this->success([
            'columns' => collect(DesignRequest::STATUSES)->map(fn (string $status): array => [
                'status' => $status,
                'label'  => str($status)->replace('_', ' ')->title()->toString(),
                'items'  => $requests[$status] ?? [],
            ])->all(),
        ], 'Design request board retrieved.');
    }

    public function workload(): JsonResponse
    {
        $this->authorizeAdmin();

        $admins = User::query()
            ->where('user_type', UserType::ADMIN)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $workload = $admins->map(function (User $admin): array {
            $assigned = DesignRequest::query()
                ->where('assigned_to', $admin->id)
                ->whereNotIn('status', ['completed', 'rejected']);

            return [
                'id'       => $admin->id,
                'name'     => $admin->name,
                'email'    => $admin->email,
                'assigned' => (clone $assigned)->count(),
                'urgent'   => (clone $assigned)->where('priority', 'urgent')->count(),
                'overdue'  => (clone $assigned)
                    ->where(function ($query): void {
                        $query->where('sla_due_at', '<', now())
                            ->orWhere('due_at', '<', now());
                    })
                    ->count(),
                'due_soon' => (clone $assigned)
                    ->whereBetween('due_at', [now(), now()->addDay()])
                    ->count(),
            ];
        });

        return $this->success([
            'assignees'  => $workload,
            'unassigned' => DesignRequest::query()
                ->whereNull('assigned_to')
                ->whereNotIn('status', ['completed', 'rejected'])
                ->count(),
        ], 'Design request workload retrieved.');
    }

    public function show(string $designRequest): JsonResponse
    {
        $this->authorizeAdmin();

        $record = $this->findRequest($designRequest);

        if (! $record) {
            return $this->error('Design request not found.', 404);
        }

        return $this->success(new DesignRequestResource($record), 'Design request retrieved.');
    }

    public function update(AdminDesignRequestStatusRequest $request, string $designRequest): JsonResponse
    {
        $this->authorizeAdmin();

        $record = $this->findRequest($designRequest);

        if (! $record) {
            return $this->error('Design request not found.', 404);
        }

        $this->service->review($record, $request->validated(), Auth::id());

        return $this->success(new DesignRequestResource($record->fresh(['files', 'events', 'tenant', 'requester', 'assignee', 'linkedTemplate'])), 'Design request updated.');
    }

    public function comment(AdminDesignRequestCommentRequest $request, string $designRequest): JsonResponse
    {
        $this->authorizeAdmin();

        $record = $this->findRequest($designRequest);

        if (! $record) {
            return $this->error('Design request not found.', 404);
        }

        return $this->success(
            new DesignRequestResource($this->service->comment($record, Auth::id(), $request->validated('message'))),
            'Comment added.',
        );
    }

    public function convertTemplateImprovement(AdminDesignRequestConversionRequest $request, string $designRequest): JsonResponse
    {
        return $this->conversionResponse($request, $designRequest, 'template_improvement', 'Request converted into a template improvement.');
    }

    public function convertCatalogChange(AdminDesignRequestConversionRequest $request, string $designRequest): JsonResponse
    {
        return $this->conversionResponse($request, $designRequest, 'catalog_change', 'Request converted into a catalog change.');
    }

    public function linkCompletedWork(AdminDesignRequestLinkRequest $request, string $designRequest): JsonResponse
    {
        $this->authorizeAdmin();

        $record = $this->findRequest($designRequest);

        if (! $record) {
            return $this->error('Design request not found.', 404);
        }

        return $this->success(new DesignRequestResource($this->service->linkCompletedWork($record, $request->validated(), Auth::id())), 'Completed work linked.');
    }

    public function notifyTenant(string $designRequest): JsonResponse
    {
        $this->authorizeAdmin();

        $record = $this->findRequest($designRequest);

        if (! $record) {
            return $this->error('Design request not found.', 404);
        }

        return $this->success(new DesignRequestResource($this->service->markNotification($record, Auth::id())), 'Notification sent.');
    }

    private function findRequest(string $id): ?DesignRequest
    {
        return DesignRequest::query()
            ->with(['files', 'events', 'tenant', 'requester', 'assignee', 'linkedTemplate'])
            ->whereKey($id)
            ->first();
    }

    private function conversionResponse(AdminDesignRequestConversionRequest $request, string $designRequest, string $type, string $message): JsonResponse
    {
        $this->authorizeAdmin();

        $record = $this->findRequest($designRequest);

        if (! $record) {
            return $this->error('Design request not found.', 404);
        }

        return $this->success(new DesignRequestResource($this->service->convert($record, $type, $request->validated(), Auth::id())), $message);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->user_type === UserType::ADMIN, 403);
    }
}
