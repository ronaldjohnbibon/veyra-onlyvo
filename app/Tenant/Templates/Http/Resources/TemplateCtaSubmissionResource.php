<?php

namespace App\Tenant\Templates\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplateCtaSubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $template = $this->relationLoaded('template') ? $this->template : null;

        return [
            'id'            => $this->id,
            'template_id'   => $this->template_id,
            'template_name' => $template?->business_name ?: $template?->name,
            'template_slug' => $template?->slug,
            'template_url'  => $template ? $this->templateUrl($request, $template->slug) : null,
            'cta_type'      => $this->cta_type,
            'payload'       => $this->payload ?? [],
            'summary'       => $this->summary($this->payload),
            'status'        => $this->status,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }

    private function templateUrl(Request $request, string $slug): string
    {
        return rtrim($request->getSchemeAndHttpHost(), '/').'/'.$slug;
    }

    private function summary(mixed $payload): string
    {
        if (! is_array($payload)) {
            return 'Form submission';
        }

        $values = collect($payload)
            ->filter(fn (mixed $value): bool => is_scalar($value) && trim((string) $value) !== '')
            ->take(2)
            ->map(fn (mixed $value): string => str((string) $value)->limit(60)->toString())
            ->values();

        return $values->isNotEmpty() ? $values->join(' - ') : 'Form submission';
    }
}
