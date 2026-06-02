<?php

namespace App\Tenant\Templates\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplateCatalogItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'website_type_id'   => $this->website_type_id,
            'website_type_slug' => $this->websiteType?->slug,
            'website_type'      => new WebsiteTypeResource($this->whenLoaded('websiteType')),
            'key'               => $this->key,
            'name'              => $this->name,
            'description'       => $this->description,
            'preview_image'     => $this->preview_image,
            'field_schema'      => $this->field_schema    ?? [],
            'default_content'   => $this->default_content ?? [],
            'is_active'         => (bool) $this->is_active,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
