<?php

namespace App\Modules\Templates\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplateDesignResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'website_type_id' => $this->website_type_id,
            'key'             => $this->key,
            'name'            => $this->name,
            'description'     => $this->description,
            'preview_image'   => $this->preview_image,
            'sections'        => $this->sections ?? [],
            'sort_order'      => $this->sort_order,
            'is_active'       => (bool) $this->is_active,
        ];
    }
}
