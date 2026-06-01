<?php

namespace App\Modules\Analytics\Services;

use App\Modules\Analytics\Models\VisitorVisit;
use App\Modules\Templates\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VisitorTrackingService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(Request $request, string $tenantId, array $payload): ?VisitorVisit
    {
        $template = $this->publishedTemplate($tenantId, (string) ($payload['template_id'] ?? ''));

        if (! $template) {
            return null;
        }

        $now         = now();
        $visitDate   = $now->toDateString();
        $ipAddress   = (string) $request->ip();
        $userAgent   = Str::limit((string) $request->userAgent(), 1000, '');
        $visitorHash = $this->visitorHash($tenantId, $ipAddress, $userAgent, $visitDate);

        $visit = VisitorVisit::create([
            'tenant_id'    => $tenantId,
            'template_id'  => $template->id,
            'url'          => Str::limit((string) ($payload['url'] ?? $request->headers->get('referer', '/')), 500, ''),
            'ip_address'   => $ipAddress,
            'user_agent'   => $userAgent,
            'referrer'     => $this->referrer($request, $payload),
            'visitor_hash' => $visitorHash,
            'visit_date'   => $visitDate,
            'visited_at'   => $now,
        ]);

        $this->recordUniqueVisitor($tenantId, $template->id, $ipAddress, $userAgent, $visitorHash, $visitDate, $now);

        return $visit;
    }

    private function publishedTemplate(string $tenantId, string $templateId): ?Template
    {
        if ($templateId === '') {
            return null;
        }

        // Public tracking only accepts published templates owned by the current tenant.
        return Template::withoutTenantRestrictions(function () use ($tenantId, $templateId): ?Template {
            return Template::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'published')
                ->find($templateId);
        });
    }

    private function visitorHash(string $tenantId, string $ipAddress, string $userAgent, string $visitDate): string
    {
        return hash('sha256', implode('|', [$tenantId, $ipAddress, $userAgent, $visitDate]));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function referrer(Request $request, array $payload): ?string
    {
        $referrer = trim((string) ($payload['referrer'] ?? $request->headers->get('referer', '')));

        return $referrer === '' ? null : Str::limit($referrer, 500, '');
    }

    private function recordUniqueVisitor(
        string $tenantId,
        string $templateId,
        string $ipAddress,
        string $userAgent,
        string $visitorHash,
        string $visitDate,
        mixed $now,
    ): void {
        // The unique index keeps refreshes from creating another daily unique visitor.
        DB::table('visitor_unique_visitors')->insertOrIgnore([
            'id'            => (string) Str::uuid(),
            'tenant_id'     => $tenantId,
            'template_id'   => $templateId,
            'ip_address'    => $ipAddress,
            'user_agent'    => $userAgent,
            'visitor_hash'  => $visitorHash,
            'visit_date'    => $visitDate,
            'first_seen_at' => $now,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }
}
