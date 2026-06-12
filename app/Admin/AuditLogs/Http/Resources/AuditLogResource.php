<?php

namespace App\Admin\AuditLogs\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $action = $this->action ?: 'activity.recorded';
        $entity = $this->entity_label ?: $this->entity_id ?: $this->entity_type ?: 'system';

        return [
            'id'        => $this->id,
            'scope'     => $this->scope ?: 'admin',
            'tenant_id' => $this->tenant_id,
            'category'  => $this->category ?: 'audit',
            'severity'  => $this->severity ?: 'info',
            'actor'     => [
                'type'  => $this->actor_type ?: 'system',
                'id'    => $this->actor_id,
                'name'  => $this->actor_name,
                'email' => $this->actor_email,
            ],
            'ip_address' => $this->ip_address,
            'entity'     => [
                'type'  => $this->entity_type ?: 'system',
                'id'    => $this->entity_id,
                'label' => $this->entity_label,
            ],
            'user_agent'     => $this->user_agent,
            'action'         => $action,
            'summary'        => str($action)->headline()->toString().' on '.$entity,
            'previous_value' => $this->previous_value,
            'new_value'      => $this->new_value,
            'metadata'       => $this->metadata,
            'occurred_at'    => $this->occurred_at?->toISOString(),
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
