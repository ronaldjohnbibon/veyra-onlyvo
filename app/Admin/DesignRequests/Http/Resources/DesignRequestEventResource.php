<?php

namespace App\Admin\DesignRequests\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignRequestEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'actor_type'  => $this->actor_type,
            'actor_name'  => $this->actor_name,
            'event_type'  => $this->event_type,
            'from_status' => $this->from_status,
            'to_status'   => $this->to_status,
            'message'     => $this->message,
            'created_at'  => $this->created_at,
        ];
    }
}
