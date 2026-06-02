<?php

namespace App\Tenant\Dashboard\Services;

use App\Tenant\Dashboard\Models\CtaEvent;
use App\Tenant\Templates\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CtaTrackingService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(Request $request, string $tenantId, array $payload): ?CtaEvent
    {
        $template = $this->publishedTemplate((string) ($payload['template_id'] ?? ''));

        if (! $template) {
            return null;
        }

        $now           = now();
        $eventDate     = $now->toDateString();
        $ipAddress     = (string) $request->ip();
        $userAgent     = Str::limit((string) $request->userAgent(), 1000, '');
        $ctaIdentifier = Str::limit((string) $payload['cta_identifier'], 191, '');
        $visitorHash   = $this->visitorHash($tenantId, $ipAddress, $userAgent, $ctaIdentifier, $eventDate);

        $event = CtaEvent::create([
            'tenant_id'      => $tenantId,
            'template_id'    => $template->id,
            'cta_identifier' => $ctaIdentifier,
            'cta_label'      => $this->nullableText($payload['cta_label'] ?? null, 255),
            'cta_type'       => Str::limit((string) $payload['cta_type'], 100, ''),
            'event_type'     => Str::limit((string) $payload['event_type'], 100, ''),
            'url'            => Str::limit((string) $payload['url'], 500, ''),
            'ip_address'     => $ipAddress,
            'user_agent'     => $userAgent,
            'referrer'       => $this->referrer($request, $payload),
            'visitor_hash'   => $visitorHash,
            'event_date'     => $eventDate,
            'triggered_at'   => $now,
        ]);

        $this->recordUniqueCtaVisitor($tenantId, $template->id, $payload, $ipAddress, $userAgent, $visitorHash, $eventDate, $now);

        return $event;
    }

    private function publishedTemplate(string $templateId): ?Template
    {
        if ($templateId === '') {
            return null;
        }

        return Template::query()
            ->where('status', 'published')
            ->find($templateId);
    }

    private function visitorHash(string $tenantId, string $ipAddress, string $userAgent, string $ctaIdentifier, string $eventDate): string
    {
        return hash('sha256', implode('|', [$tenantId, $ipAddress, $userAgent, $ctaIdentifier, $eventDate]));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function referrer(Request $request, array $payload): ?string
    {
        $referrer = trim((string) ($payload['referrer'] ?? $request->headers->get('referer', '')));

        return $referrer === '' ? null : Str::limit($referrer, 500, '');
    }

    private function nullableText(mixed $value, int $limit): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : Str::limit($text, $limit, '');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordUniqueCtaVisitor(
        string $tenantId,
        string $templateId,
        array $payload,
        string $ipAddress,
        string $userAgent,
        string $visitorHash,
        string $eventDate,
        mixed $now,
    ): void {
        // The unique index keeps repeat clicks from creating another daily CTA visitor.
        DB::table('cta_unique_visitors')->insertOrIgnore([
            'id'             => (string) Str::uuid(),
            'tenant_id'      => $tenantId,
            'template_id'    => $templateId,
            'cta_identifier' => Str::limit((string) $payload['cta_identifier'], 191, ''),
            'cta_label'      => $this->nullableText($payload['cta_label'] ?? null, 255),
            'cta_type'       => Str::limit((string) $payload['cta_type'], 100, ''),
            'ip_address'     => $ipAddress,
            'user_agent'     => $userAgent,
            'visitor_hash'   => $visitorHash,
            'event_date'     => $eventDate,
            'first_seen_at'  => $now,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);
    }
}
