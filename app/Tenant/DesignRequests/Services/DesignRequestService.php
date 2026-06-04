<?php

namespace App\Tenant\DesignRequests\Services;

use App\Tenant\DesignRequests\Models\DesignRequest;
use App\Tenant\Users\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DesignRequestService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): DesignRequest
    {
        return DB::transaction(function () use ($user, $data): DesignRequest {
            $files = $data['files'] ?? [];
            unset($data['files']);

            // Store the request before attaching uploaded mockup files.
            $request = DesignRequest::query()->create(array_merge($data, [
                'tenant_id' => (string) $user->tenant_id,
                'user_id'   => $user->id,
                'status'    => 'pending',
            ]));

            $this->storeFiles($request, is_array($files) ? $files : []);
            $this->recordEvent($request, 'tenant', $user->name, $user->id, 'created', null, 'pending', 'Request submitted.');

            return $request->fresh(['files', 'tenant', 'requester']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function review(DesignRequest $request, array $data, int|string|null $reviewerId): DesignRequest
    {
        $status = (string) $data['status'];
        $now    = Carbon::now();

        $request->update([
            'status'        => $status,
            'admin_remarks' => $data['admin_remarks'] ?? null,
            'reviewed_by'   => $reviewerId,
            'reviewed_at'   => $now,
            'completed_at'  => $status === 'completed' ? $now : null,
        ]);

        return $request;
    }

    public function comment(DesignRequest $request, User $user, string $message): DesignRequest
    {
        $this->recordEvent($request, 'tenant', $user->name, $user->id, 'comment', null, null, $message);

        return $request->fresh(['files', 'events', 'tenant', 'requester']);
    }

    public function tenantAction(DesignRequest $request, User $user, string $action, ?string $message = null): DesignRequest
    {
        $fromStatus = $request->status;
        $toStatus   = $action === 'approve' ? 'approved' : 'changes_requested';
        $eventType  = $action === 'approve' ? 'approval' : 'changes_requested';

        $request->update([
            'status'       => $toStatus,
            'reviewed_at'  => now(),
            'completed_at' => null,
        ]);

        $this->recordEvent(
            $request,
            'tenant',
            $user->name,
            $user->id,
            $eventType,
            $fromStatus,
            $toStatus,
            $message ?: ($action === 'approve' ? 'Tenant approved the current direction.' : 'Tenant requested changes.')
        );

        return $request->fresh(['files', 'events', 'tenant', 'requester']);
    }

    /**
     * @param  array<int, mixed>  $files
     */
    private function storeFiles(DesignRequest $request, array $files): void
    {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->storePublicly(
                "design-requests/{$request->tenant_id}/{$request->id}",
                'public',
            );

            $request->files()->create([
                'name'      => $file->getClientOriginalName(),
                'path'      => $path,
                'mime_type' => $file->getClientMimeType(),
                'size'      => $file->getSize() ?: 0,
            ]);
        }
    }

    private function recordEvent(
        DesignRequest $request,
        string $actorType,
        ?string $actorName,
        int|string|null $actorId,
        string $eventType,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $message,
    ): void {
        $request->events()->create([
            'actor_type'  => $actorType,
            'actor_name'  => $actorName,
            'actor_id'    => $actorId,
            'event_type'  => $eventType,
            'from_status' => $fromStatus,
            'to_status'   => $toStatus,
            'message'     => $message,
        ]);
    }
}
