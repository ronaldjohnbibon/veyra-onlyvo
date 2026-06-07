<?php

namespace App\Admin\DesignRequests\Services;

use App\Admin\AuditLogs\Services\AuditLogService;
use App\Admin\DesignRequests\Models\DesignRequest;
use App\Admin\Users\Models\User;
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

            return $request->fresh(['files', 'tenant', 'requester']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function review(DesignRequest $request, array $data, int|string|null $reviewerId): DesignRequest
    {
        $status       = (string) $data['status'];
        $fromStatus   = $request->status;
        $fromAssignee = $request->assigned_to;
        $fromPriority = $request->priority;
        $now          = Carbon::now();
        $previous     = $request->only([
            'status',
            'assigned_to',
            'priority',
            'due_at',
            'sla_due_at',
            'admin_remarks',
            'internal_notes',
        ]);

        $request->update([
            'status'         => $status,
            'assigned_to'    => $data['assigned_to']    ?? null,
            'priority'       => $data['priority']       ?? 'normal',
            'due_at'         => $data['due_at']         ?? null,
            'sla_due_at'     => $data['sla_due_at']     ?? null,
            'admin_remarks'  => $data['admin_remarks']  ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'reviewed_by'    => $reviewerId,
            'reviewed_at'    => $now,
            'completed_at'   => $status === 'completed' ? $now : null,
        ]);

        $this->recordEvent(
            $request,
            'admin',
            $this->actorName($reviewerId),
            $reviewerId,
            $fromStatus === $status ? 'feedback' : 'status_changed',
            $fromStatus,
            $status,
            $data['admin_remarks'] ?? null,
        );

        if ((string) $fromAssignee !== (string) ($data['assigned_to'] ?? '')) {
            $this->recordEvent($request, 'admin', $this->actorName($reviewerId), $reviewerId, 'assigned', null, null, 'Assigned to '.$this->actorName($data['assigned_to'] ?? null));
        }

        if ($fromPriority !== ($data['priority'] ?? 'normal')) {
            $this->recordEvent($request, 'admin', $this->actorName($reviewerId), $reviewerId, 'priority_changed', null, null, 'Priority changed to '.($data['priority'] ?? 'normal'));
        }

        $this->audit(
            'design_request.updated',
            $request->fresh(),
            $reviewerId,
            $previous,
            $request->fresh()?->only([
                'status',
                'assigned_to',
                'priority',
                'due_at',
                'sla_due_at',
                'admin_remarks',
                'internal_notes',
            ]),
        );

        return $request;
    }

    public function comment(DesignRequest $request, int|string|null $adminId, string $message): DesignRequest
    {
        $this->recordEvent($request, 'admin', $this->actorName($adminId), $adminId, 'comment', null, null, $message);
        $this->audit('design_request.commented', $request, $adminId, null, ['message' => $message]);

        return $request->fresh(['files', 'events', 'tenant', 'requester']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function convert(DesignRequest $request, string $type, array $data, int|string|null $adminId): DesignRequest
    {
        $previous = $request->only(['conversion_type', 'conversion_payload', 'converted_at', 'converted_by']);

        $request->update([
            'conversion_type'    => $type,
            'conversion_payload' => [
                'summary'    => $data['summary']    ?? null,
                'changelog'  => $data['changelog']  ?? null,
                'target_key' => $data['target_key'] ?? null,
            ],
            'converted_at' => now(),
            'converted_by' => $adminId,
        ]);

        $this->recordEvent(
            $request,
            'admin',
            $this->actorName($adminId),
            $adminId,
            $type === 'template_improvement' ? 'converted_to_template_improvement' : 'converted_to_catalog_change',
            null,
            null,
            $data['summary'] ?? null,
        );

        $this->audit('design_request.converted', $request->fresh(), $adminId, $previous, $request->fresh()?->only(['conversion_type', 'conversion_payload', 'converted_at', 'converted_by']));

        return $request->fresh(['files', 'events', 'tenant', 'requester', 'assignee']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function linkCompletedWork(DesignRequest $request, array $data, int|string|null $adminId): DesignRequest
    {
        $previous = $request->only(['linked_template_id', 'linked_site_url']);

        $request->update([
            'linked_template_id' => $data['linked_template_id'] ?? null,
            'linked_site_url'    => $data['linked_site_url']    ?? null,
        ]);

        $this->recordEvent($request, 'admin', $this->actorName($adminId), $adminId, 'linked_completed_work', null, null, $data['linked_site_url'] ?? null);
        $this->audit('design_request.linked_completed_work', $request->fresh(), $adminId, $previous, $request->fresh()?->only(['linked_template_id', 'linked_site_url']));

        return $request->fresh(['files', 'events', 'tenant', 'requester', 'assignee']);
    }

    public function markNotification(DesignRequest $request, int|string|null $adminId): DesignRequest
    {
        $previous = $request->only(['notification_requested', 'notification_sent_at']);

        $request->update([
            'notification_requested' => true,
            'notification_sent_at'   => now(),
        ]);

        $this->recordEvent($request, 'admin', $this->actorName($adminId), $adminId, 'notification_marked', null, null, 'Notification marked for tenant follow-up.');
        $this->audit('design_request.notification_marked', $request->fresh(), $adminId, $previous, $request->fresh()?->only(['notification_requested', 'notification_sent_at']));

        return $request->fresh(['files', 'events', 'tenant', 'requester', 'assignee']);
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

    private function actorName(int|string|null $actorId): ?string
    {
        if (! $actorId) {
            return null;
        }

        return User::query()->find($actorId)?->name;
    }

    private function actor(int|string|null $actorId): ?User
    {
        if (! $actorId) {
            return null;
        }

        return User::query()->find($actorId);
    }

    private function audit(string $action, ?DesignRequest $request, int|string|null $actorId, mixed $previousValue, mixed $newValue): void
    {
        if (! $request) {
            return;
        }

        app(AuditLogService::class)->recordModel(
            $action,
            $request,
            $this->actor($actorId),
            request(),
            $previousValue,
            $newValue,
            'design_request',
            $request->title,
            [
                'tenant_id' => $request->tenant_id,
            ],
        );
    }
}
