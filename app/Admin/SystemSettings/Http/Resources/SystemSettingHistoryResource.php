<?php

namespace App\Admin\SystemSettings\Http\Resources;

use App\Admin\SystemSettings\Services\SystemSettingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemSettingHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $settings = app(SystemSettingService::class);

        return [
            'id'             => $this->id,
            'setting_key'    => $this->setting_key,
            'action'         => $this->action,
            'previous_value' => $settings->displayValue((string) $this->setting_key, $this->previous_value),
            'new_value'      => $settings->displayValue((string) $this->setting_key, $this->new_value),
            'can_restore'    => $settings->definitionExists((string) $this->setting_key),
            'changed_by'     => [
                'id'    => $this->changed_by_user_id,
                'name'  => $this->changed_by_name,
                'email' => $this->changed_by_email,
            ],
            'changed_at' => optional($this->changed_at)->toISOString(),
        ];
    }
}
