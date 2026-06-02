<?php

namespace App\Modules\DesignRequests\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Enums\UserType;
use App\Modules\DesignRequests\Http\Requests\AdminDesignRequestStatusRequest;
use App\Modules\DesignRequests\Http\Resources\DesignRequestResource;
use App\Modules\DesignRequests\Models\DesignRequest;
use App\Modules\DesignRequests\Services\DesignRequestService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
            'title'      => 'title',
        ];
        $sort       = (string) $request->input('sort', 'created_at');
        $sortColumn = $sorts[$sort] ?? 'created_at';
        $direction  = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $requests = DesignRequest::withoutTenantRestrictions(function () use ($request, $sortColumn, $direction): LengthAwarePaginator {
            return DesignRequest::query()
                ->with(['files', 'tenant', 'requester'])
                ->filter([
                    'search'    => $request->input('search'),
                    'status'    => $request->input('status'),
                    'tenant_id' => $request->input('tenant_id'),
                ])
                ->orderBy($sortColumn, $direction)
                ->orderBy('title')
                ->paginate(
                    (int) $request->input('pageSize', 15),
                    ['*'],
                    'page',
                    (int) $request->input('page', 1),
                );
        });

        return $this->success(DesignRequestResource::collection($requests), 'Design requests retrieved.');
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

        return $this->success(new DesignRequestResource($record->fresh(['files', 'tenant', 'requester'])), 'Design request updated.');
    }

    private function findRequest(string $id): ?DesignRequest
    {
        return DesignRequest::withoutTenantRestrictions(function () use ($id): ?DesignRequest {
            return DesignRequest::query()
                ->with(['files', 'tenant', 'requester'])
                ->whereKey($id)
                ->first();
        });
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->user_type === UserType::ADMIN, 403);
    }
}
