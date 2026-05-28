<?php

namespace App\Modules\DesignRequests\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DesignRequests\Http\Requests\DesignRequestRequest;
use App\Modules\DesignRequests\Http\Resources\DesignRequestResource;
use App\Modules\DesignRequests\Models\DesignRequest;
use App\Modules\DesignRequests\Services\DesignRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DesignRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
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

    public function store(DesignRequestRequest $request, DesignRequestService $service): JsonResponse
    {
        $user = Auth::user();

        abort_unless($user?->tenant_id, 403);

        $designRequest = $service->create($user, $request->validated());

        return $this->success(new DesignRequestResource($designRequest), 'Design request submitted.', 201);
    }

    public function show(string $designRequest): JsonResponse
    {
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
