<?php

namespace App\Admin\Sidebar\Http\Resources;

use App\Admin\Auth\Http\Resources\TenantResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SidebarResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data      = is_array($this->data) ? $this->data : [];
        $mainNav   = is_array($data['main_nav'] ?? null) ? $data['main_nav'] : [];
        $linkCount = 0;

        foreach ($mainNav as $item) {
            if (! is_array($item)) {
                continue;
            }

            $linkCount++;

            if (! empty($item['items']) && is_array($item['items'])) {
                $linkCount += count($item['items']);
            }
        }

        return [
            'id'          => $this->id,
            'tenant_id'   => $this->tenant_id,
            'name'        => $this->name,
            'description' => $this->description,
            'is_admin'    => (bool) $this->is_admin,
            'data'        => $data,
            'stats'       => [
                'nav_groups' => count($mainNav),
                'links'      => $linkCount,
            ],
            'tenant'     => new TenantResource($this->whenLoaded('tenant')),
            'updated_at' => $this->updated_at,
        ];
    }
}
