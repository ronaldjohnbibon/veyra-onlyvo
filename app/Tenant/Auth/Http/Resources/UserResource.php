<?php

namespace App\Tenant\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'tenant_id'   => $this->tenant_id,
            'name'        => $this->name,
            'first_name'  => $this->first_name,
            'last_name'   => $this->last_name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'tenant'      => new TenantResource($this->whenLoaded('tenant')),
            'roles'       => [],
            'permissions' => [],
            'user_type'   => $this->user_type?->value ?? $this->user_type,
            'is_active'   => (bool) $this->is_active,
        ];
    }
}
