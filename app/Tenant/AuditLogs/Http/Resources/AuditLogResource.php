<?php

namespace App\Tenant\AuditLogs\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'scope'     => $this->scope,
            'tenant_id' => $this->tenant_id,
            'category'  => $this->category,
            'severity'  => $this->severity,
            'actor'     => [
                'type'  => $this->actor_type,
                'id'    => $this->actor_id,
                'name'  => $this->actor_name,
                'email' => $this->actor_email,
            ],
            'entity' => [
                'type'  => $this->entity_type,
                'id'    => $this->entity_id,
                'label' => $this->entity_label,
            ],
            'ip_address'     => $this->ip_address,
            'user_agent'     => $this->user_agent,
            'action'         => $this->action,
            'summary'        => str($this->action)->headline()->toString().' on '.($this->entity_label ?: $this->entity_id ?: $this->entity_type),
            'previous_value' => $this->previous_value,
            'new_value'      => $this->new_value,
            'metadata'       => $this->metadata,
            'occurred_at'    => $this->occurred_at?->toISOString(),
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
