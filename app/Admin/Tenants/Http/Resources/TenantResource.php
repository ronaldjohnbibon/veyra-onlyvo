<?php

namespace App\Admin\Tenants\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'subdomain' => $this->subdomain,
            'settings'  => $this->settings,
            'timezone'  => $this->timezone,
            'status'    => $this->status,
            'owner'     => $this->whenLoaded('owner', fn () => [
                'id'         => $this->owner?->id,
                'name'       => $this->owner?->name,
                'first_name' => $this->owner?->first_name,
                'last_name'  => $this->owner?->last_name,
                'email'      => $this->owner?->email,
                'phone'      => $this->owner?->phone,
            ]),
            'users_count'     => $this->whenCounted('users'),
            'templates_count' => $this->whenCounted('templates'),
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
        ];
    }
}
