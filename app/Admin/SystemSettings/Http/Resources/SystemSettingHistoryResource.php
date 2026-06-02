<?php

namespace App\Admin\SystemSettings\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemSettingHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'setting_key'    => $this->setting_key,
            'previous_value' => $this->previous_value,
            'new_value'      => $this->new_value,
            'changed_by'     => [
                'id'    => $this->changed_by_user_id,
                'name'  => $this->changed_by_name,
                'email' => $this->changed_by_email,
            ],
            'changed_at'     => optional($this->changed_at)->toISOString(),
        ];
    }
}
