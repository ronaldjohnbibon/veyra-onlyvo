<?php

namespace App\Modules\Templates\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplateSectionDesignResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'section_type'         => $this->section_type,
            'section_label'        => (string) ($this->section_label ?? $this->section_type),
            'name'                 => $this->name,
            'design_key'           => $this->design_key,
            'preview_image'        => $this->preview_image,
            'fields_json'          => $this->fields_json          ?? [],
            'default_content_json' => $this->default_content_json ?? [],
            'default_enabled'      => (bool) $this->default_enabled,
            'default_sort_order'   => (int) $this->default_sort_order,
            'is_active'            => (bool) $this->is_active,
        ];
    }
}
