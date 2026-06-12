<?php

namespace App\Admin\Leads\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class AdminLeadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $template = $this->relationLoaded('template') ? $this->template : null;
        $tenant   = $template?->tenant;

        return [
            'id'               => $this->id,
            'tenant_id'        => $tenant?->id,
            'tenant_name'      => $tenant?->name,
            'tenant_subdomain' => $tenant?->subdomain,
            'template_id'      => $this->template_id,
            'template_name'    => $template?->business_name ?: $template?->name,
            'template_slug'    => $template?->slug,
            'cta_type'         => $this->cta_type,
            'payload'          => $this->payload ?? [],
            'summary'          => $this->summary($this->payload),
            'status'           => $this->status,
            'links'            => [
                'tenant'        => $tenant ? '/admin/tenants/'.$tenant->id : null,
                'public_site'   => $tenant?->subdomain && $template?->slug ? $this->tenantUrl($request, $tenant->subdomain, '/'.$template->slug) : null,
                'tracking_logs' => $tenant?->subdomain ? $this->tenantUrl($request, $tenant->subdomain, '/tracking-logs?event_type=form_submission') : null,
                'analytics'     => $tenant?->subdomain ? $this->tenantUrl($request, $tenant->subdomain, '/analytics') : null,
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function tenantUrl(Request $request, string $subdomain, string $path): string
    {
        $host  = $request->getHost();
        $parts = explode('.', $host);

        if (count($parts) > 2 && in_array($parts[0], ['admin', 'www'], true)) {
            array_shift($parts);
            $host = implode('.', $parts);
        }

        $port        = $request->getPort();
        $portSegment = in_array($port, [80, 443], true) ? '' : ':'.$port;

        return $request->getScheme().'://'.$subdomain.'.'.$host.$portSegment.$path;
    }

    private function summary(mixed $payload): string
    {
        if (! is_array($payload)) {
            return 'Form submission';
        }

        $values = collect($payload)
            ->filter(fn (mixed $value): bool => is_scalar($value) && trim((string) $value) !== '')
            ->take(2)
            ->map(fn (mixed $value): string => Str::limit((string) $value, 60))
            ->values();

        return $values->isNotEmpty() ? $values->join(' - ') : 'Form submission';
    }
}
