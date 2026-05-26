<?php

namespace App\Modules\Templates\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'tenant_id'        => $this->tenant_id,
            'website_type_id'  => $this->website_type_id,
            'website_type'     => new WebsiteTypeResource($this->whenLoaded('websiteType')),
            'name'             => $this->name,
            'slug'             => $this->slug,
            'template_key'     => $this->template_key,
            'business_name'    => $this->business_name,
            'logo'             => $this->logo,
            'contact_info'     => $this->contact_info ?? [],
            'social_links'     => $this->social_links ?? [],
            'content'          => $this->content      ?? [],
            'font_family'      => $this->font_family,
            'primary_color'    => $this->primary_color,
            'secondary_color'  => $this->secondary_color,
            'background_color' => $this->background_color,
            'text_color'       => $this->text_color,
            'status'           => $this->status,
            'is_default'       => (bool) $this->is_default,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}
