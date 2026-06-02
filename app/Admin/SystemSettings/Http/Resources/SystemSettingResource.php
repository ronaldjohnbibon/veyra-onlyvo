<?php

namespace App\Admin\SystemSettings\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'group'      => $this->group,
            'key'        => $this->key,
            'label'      => $this->label,
            'type'       => $this->type,
            'value'      => $this->value,
            'is_public'  => $this->is_public,
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
