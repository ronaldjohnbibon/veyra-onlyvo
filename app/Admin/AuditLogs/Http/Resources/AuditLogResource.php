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
        return [
            'id'    => $this->id,
            'actor' => [
                'type'  => $this->actor_type,
                'id'    => $this->actor_id,
                'name'  => $this->actor_name,
                'email' => $this->actor_email,
            ],
            'ip_address' => $this->ip_address,
            'entity'     => [
                'type'  => $this->entity_type,
                'id'    => $this->entity_id,
                'label' => $this->entity_label,
            ],
            'action'         => $this->action,
            'previous_value' => $this->previous_value,
            'new_value'      => $this->new_value,
            'metadata'       => $this->metadata,
            'occurred_at'    => $this->occurred_at?->toISOString(),
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
