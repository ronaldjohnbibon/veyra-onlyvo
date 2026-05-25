<?php

namespace App\Modules\Posts\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'template_id'    => $this->template_id,
            'title'          => $this->title,
            'slug'           => $this->slug,
            'content'        => $this->content,
            'excerpt'        => str(strip_tags((string) $this->content))->limit(180)->toString(),
            'featured_image' => $this->featured_image,
            'status'         => $this->status,
            'published_at'   => $this->published_at,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
