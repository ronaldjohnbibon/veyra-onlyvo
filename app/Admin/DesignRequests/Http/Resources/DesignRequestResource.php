<?php

namespace App\Admin\DesignRequests\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'tenant_id'       => $this->tenant_id,
            'tenant_name'     => $this->whenLoaded('tenant', fn () => $this->tenant?->name),
            'requester_name'  => $this->whenLoaded('requester', fn () => $this->requester?->name),
            'title'           => $this->title,
            'description'     => $this->description,
            'notes'           => $this->notes,
            'reference_links' => $this->reference_links ?? [],
            'mockup_concept'  => $this->mockup_concept,
            'status'          => $this->status,
            'status_label'    => str((string) $this->status)->replace('_', ' ')->title()->toString(),
            'status_explanation' => $this->statusExplanation((string) $this->status),
            'admin_remarks'   => $this->admin_remarks,
            'files'           => DesignRequestFileResource::collection($this->whenLoaded('files')),
            'events'          => DesignRequestEventResource::collection($this->whenLoaded('events')),
            'reviewed_at'     => $this->reviewed_at,
            'completed_at'    => $this->completed_at,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }

    private function statusExplanation(string $status): string
    {
        return match ($status) {
            'pending' => 'The request is waiting for admin triage.',
            'under_review' => 'The request is actively being reviewed with the tenant.',
            'approved' => 'The current direction has been approved.',
            'changes_requested' => 'The tenant or admin requested changes before completion.',
            'rejected' => 'The request was declined or cannot be completed as submitted.',
            'completed' => 'The design request has been completed.',
            default => 'The request status has been updated.',
        };
    }
}
