<?php

namespace App\Modules\Templates\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplateSectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'section_type' => $this->section_type,
            'design_key'   => $this->design_key,
            'sort_order'   => (int) $this->sort_order,
            'is_enabled'   => (bool) $this->is_enabled,
            'content_json' => $this->content_json ?? [],
        ];
    }
}
