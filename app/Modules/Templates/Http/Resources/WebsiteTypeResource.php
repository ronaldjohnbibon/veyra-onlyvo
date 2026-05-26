<?php

namespace App\Modules\Templates\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebsiteTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'slug'            => $this->slug,
            'description'     => $this->description,
            'sort_order'      => $this->sort_order,
            'is_active'       => (bool) $this->is_active,
            'designs_count'   => (int) ($this->template_designs_count ?? 0),
            'templates_count' => (int) ($this->templates_count ?? 0),
        ];
    }
}
