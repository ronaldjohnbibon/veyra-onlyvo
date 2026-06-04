<?php

namespace App\Tenant\TrackingLogs\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TrackingLogService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function index(string $tenantId, array $filters): array
    {
        $query = DB::query()->fromSub($this->baseUnion($tenantId), 'logs');

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate(
            (int) ($filters['pageSize'] ?? 15),
            ['*'],
            'page',
            (int) ($filters['page'] ?? 1),
        );

        return [
            'data'       => $paginator->getCollection()->map(fn (object $row): array => $this->formatRow($row))->values(),
            'pagination' => $this->pagination($paginator),
            'filters'    => $this->filterOptions($tenantId),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(string $tenantId, array $filters): array
    {
        $query = DB::query()->fromSub($this->baseUnion($tenantId), 'logs');

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        return $query
            ->limit(5000)
            ->get()
            ->map(fn (object $row): array => $this->formatRow($row))
            ->values()
            ->all();
    }

    private function baseUnion(string $tenantId): Builder
    {
        $visits = DB::table('visitor_visits as visits')
            ->leftJoin('tenants', 'tenants.id', '=', 'visits.tenant_id')
            ->leftJoin('templates', 'templates.id', '=', 'visits.template_id')
            ->where('visits.tenant_id', $tenantId)
            ->select([
                DB::raw("'visitor_visit' as source"),
                'visits.id as source_id',
                DB::raw("'website_visit' as event_type"),
                DB::raw("'Website Visit' as event_name"),
                'visits.tenant_id',
                'tenants.name as tenant_name',
                'visits.template_id',
                'templates.name as template_name',
                'templates.business_name as business_name',
                'templates.template_key as template_key',
                'visits.url as landing_page_url',
                'visits.visitor_hash as visitor_identifier',
                DB::raw('null as session_identifier'),
                'visits.referrer as referrer_url',
                'visits.user_agent',
                'visits.ip_address',
                DB::raw('null as country_location'),
                DB::raw('null as conversion_status'),
                'visits.created_at',
            ]);

        $ctaEvents = DB::table('cta_events as events')
            ->leftJoin('tenants', 'tenants.id', '=', 'events.tenant_id')
            ->leftJoin('templates', 'templates.id', '=', 'events.template_id')
            ->where('events.tenant_id', $tenantId)
            ->select([
                DB::raw("'cta_event' as source"),
                'events.id as source_id',
                DB::raw($this->ctaEventTypeCase().' as event_type'),
                DB::raw('COALESCE(NULLIF(events.cta_label, \'\'), events.cta_identifier, events.event_type) as event_name'),
                'events.tenant_id',
                'tenants.name as tenant_name',
                'events.template_id',
                'templates.name as template_name',
                'templates.business_name as business_name',
                'templates.template_key as template_key',
                'events.url as landing_page_url',
                'events.visitor_hash as visitor_identifier',
                DB::raw('null as session_identifier'),
                'events.referrer as referrer_url',
                'events.user_agent',
                'events.ip_address',
                DB::raw('null as country_location'),
                DB::raw($this->ctaConversionStatusCase().' as conversion_status'),
                'events.created_at',
            ]);

        $submissions = DB::table('template_cta_submissions as submissions')
            ->leftJoin('templates', 'templates.id', '=', 'submissions.template_id')
            ->leftJoin('tenants', 'tenants.id', '=', 'templates.tenant_id')
            ->where('templates.tenant_id', $tenantId)
            ->select([
                DB::raw("'template_cta_submission' as source"),
                'submissions.id as source_id',
                DB::raw("'form_submission' as event_type"),
                DB::raw('submissions.cta_type as event_name'),
                'templates.tenant_id',
                'tenants.name as tenant_name',
                'submissions.template_id',
                'templates.name as template_name',
                'templates.business_name as business_name',
                'templates.template_key as template_key',
                DB::raw('null as landing_page_url'),
                DB::raw('null as visitor_identifier'),
                DB::raw('null as session_identifier'),
                DB::raw('null as referrer_url'),
                DB::raw('null as user_agent'),
                DB::raw('null as ip_address'),
                DB::raw('null as country_location'),
                'submissions.status as conversion_status',
                'submissions.created_at',
            ]);

        return $visits->unionAll($ctaEvents)->unionAll($submissions);
    }

    private function ctaEventTypeCase(): string
    {
        $submittedTypes = "'form_submitted', 'booking_submitted', 'message_submitted', 'quote_request_submitted', 'newsletter_signup_submitted'";

        return "case when events.event_type in ('cta_view', 'form_opened') then 'cta_view' when events.event_type in ({$submittedTypes}) then 'conversion' else 'cta_click' end";
    }

    private function ctaConversionStatusCase(): string
    {
        $submittedTypes = "'form_submitted', 'booking_submitted', 'message_submitted', 'quote_request_submitted', 'newsletter_signup_submitted'";

        return "case when events.event_type in ({$submittedTypes}) then 'success' else null end";
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->when($filters['event_type'] ?? null, fn (Builder $query, string $eventType) => $query->where('event_type', $eventType))
            ->when($filters['template_id'] ?? null, fn (Builder $query, string $templateId) => $query->where('template_id', $templateId))
            ->when($filters['conversion_status'] ?? null, fn (Builder $query, string $status) => $query->where('conversion_status', $status))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $like = '%'.Str::lower($search).'%';

                $query->where(function (Builder $query) use ($like): void {
                    foreach (['event_name', 'tenant_name', 'template_name', 'business_name', 'landing_page_url', 'visitor_identifier', 'referrer_url', 'ip_address'] as $column) {
                        $query->orWhereRaw('lower(coalesce('.$column.", '')) like ?", [$like]);
                    }
                });
            });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applySorting(Builder $query, array $filters): void
    {
        $sortMap = [
            'created_at' => 'created_at',
            'event_type' => 'event_type',
            'event_name' => 'event_name',
            'tenant'     => 'tenant_name',
            'template'   => 'template_name',
        ];

        $sort      = $sortMap[(string) ($filters['sort'] ?? 'created_at')] ?? 'created_at';
        $direction = (string) ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sort, $direction)->orderBy('source_id', 'desc');
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRow(object $row): array
    {
        $utm = $this->utmValues((string) ($row->landing_page_url ?? ''));

        return [
            'id'                 => $row->source.'-'.$row->source_id,
            'source'             => $row->source,
            'source_id'          => $row->source_id,
            'event_type'         => $row->event_type,
            'event_type_label'   => $this->eventTypeLabel((string) $row->event_type),
            'event_name'         => $this->titleText((string) ($row->event_name ?? '')),
            'activity_title'     => $this->activityTitle((string) $row->event_type, (string) ($row->event_name ?? '')),
            'activity_summary'   => $this->activitySummary($row),
            'tenant_id'          => $row->tenant_id,
            'tenant_name'        => $row->tenant_name,
            'template_id'        => $row->template_id,
            'template_name'      => $row->template_name,
            'website_template'   => $this->websiteTemplate($row),
            'landing_page_url'   => $row->landing_page_url,
            'visitor_identifier' => $row->visitor_identifier,
            'visitor_label'      => $this->safeIdentifier((string) ($row->visitor_identifier ?? 'visitor')),
            'session_identifier' => $row->session_identifier,
            'session_label'      => $this->safeIdentifier((string) ($row->session_identifier ?? $row->visitor_identifier ?? 'session')),
            'referrer_url'       => $row->referrer_url,
            'utm_source'         => $utm['utm_source'],
            'utm_medium'         => $utm['utm_medium'],
            'utm_campaign'       => $utm['utm_campaign'],
            'device_type'        => $this->deviceType((string) ($row->user_agent ?? '')),
            'browser'            => $this->browser((string) ($row->user_agent ?? '')),
            'operating_system'   => $this->operatingSystem((string) ($row->user_agent ?? '')),
            'ip_address'         => $row->ip_address,
            'ip_address_label'   => $this->maskedIp((string) ($row->ip_address ?? '')),
            'country_location'   => $row->country_location,
            'conversion_status'  => $row->conversion_status,
            'created_at'         => $row->created_at,
        ];
    }

    private function eventTypeLabel(string $eventType): string
    {
        return match ($eventType) {
            'website_visit'   => 'Website Visit',
            'cta_view'        => 'CTA View',
            'cta_click'       => 'CTA Click',
            'form_submission' => 'Form Submission',
            'conversion'      => 'Conversion',
            default           => $this->titleText($eventType),
        };
    }

    private function activityTitle(string $eventType, string $eventName): string
    {
        $name = $this->titleText($eventName);

        return match ($eventType) {
            'website_visit'   => 'Visited a website page',
            'cta_view'        => 'Saw '.$name,
            'cta_click'       => 'Clicked '.$name,
            'form_submission' => 'Submitted '.$name,
            'conversion'      => 'Completed '.$name,
            default           => $name,
        };
    }

    private function activitySummary(object $row): string
    {
        $parts = array_filter([
            $this->websiteTemplate($row),
            $row->landing_page_url ? parse_url((string) $row->landing_page_url, PHP_URL_PATH) ?: $row->landing_page_url : null,
            $row->referrer_url ? 'from '.parse_url((string) $row->referrer_url, PHP_URL_HOST) : null,
        ]);

        return $parts ? implode(' - ', $parts) : 'No page details captured.';
    }

    private function titleText(string $value): string
    {
        $text = trim(str_replace('_', ' ', $value));

        return $text === '' ? 'Unknown' : Str::title($text);
    }

    private function websiteTemplate(object $row): string
    {
        $name = trim((string) ($row->business_name ?: $row->template_name ?: $row->template_key));

        return $name === '' ? 'Unknown template' : $name;
    }

    /**
     * @return array{utm_source: ?string, utm_medium: ?string, utm_campaign: ?string}
     */
    private function utmValues(string $url): array
    {
        $queryString = (string) (parse_url($url, PHP_URL_QUERY) ?: '');
        $params      = [];

        parse_str($queryString, $params);

        return [
            'utm_source'   => $this->nullableParam($params['utm_source'] ?? null),
            'utm_medium'   => $this->nullableParam($params['utm_medium'] ?? null),
            'utm_campaign' => $this->nullableParam($params['utm_campaign'] ?? null),
        ];
    }

    private function nullableParam(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function deviceType(string $userAgent): ?string
    {
        if ($userAgent === '') {
            return null;
        }

        $lower = Str::lower($userAgent);

        if (str_contains($lower, 'bot') || str_contains($lower, 'crawler')) {
            return 'Bot';
        }

        if (str_contains($lower, 'ipad') || str_contains($lower, 'tablet')) {
            return 'Tablet';
        }

        if (str_contains($lower, 'mobile') || str_contains($lower, 'iphone') || str_contains($lower, 'android')) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    private function browser(string $userAgent): ?string
    {
        if ($userAgent === '') {
            return null;
        }

        return match (true) {
            str_contains($userAgent, 'Edg/')     || str_contains($userAgent, 'Edge/')      => 'Edge',
            str_contains($userAgent, 'OPR/')     || str_contains($userAgent, 'Opera')      => 'Opera',
            str_contains($userAgent, 'Chrome/')  || str_contains($userAgent, 'CriOS/')  => 'Chrome',
            str_contains($userAgent, 'Firefox/') || str_contains($userAgent, 'FxiOS/') => 'Firefox',
            str_contains($userAgent, 'Safari/')                                        => 'Safari',
            default                                                                    => 'Other',
        };
    }

    private function operatingSystem(string $userAgent): ?string
    {
        if ($userAgent === '') {
            return null;
        }

        return match (true) {
            str_contains($userAgent, 'Windows')                                         => 'Windows',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')      => 'iOS',
            str_contains($userAgent, 'Mac OS') || str_contains($userAgent, 'Macintosh') => 'macOS',
            str_contains($userAgent, 'Android')                                         => 'Android',
            str_contains($userAgent, 'Linux')                                           => 'Linux',
            default                                                                     => 'Other',
        };
    }

    private function safeIdentifier(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return 'Unknown';
        }

        return 'ID '.Str::upper(substr(hash('sha256', $value), 0, 8));
    }

    private function maskedIp(string $ipAddress): string
    {
        if ($ipAddress === '') {
            return 'Not captured';
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ipAddress);

            return $parts[0].'.'.$parts[1].'.'.$parts[2].'.x';
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return substr($ipAddress, 0, 8).'...';
        }

        return 'Masked';
    }

    /**
     * @return array<string, mixed>
     */
    private function filterOptions(string $tenantId): array
    {
        $templates = DB::table('templates')
            ->select(['id', 'name', 'business_name'])
            ->where('tenant_id', $tenantId)
            ->orderBy('business_name')
            ->orderBy('name')
            ->get()
            ->map(fn (object $template): array => [
                'id'   => (string) $template->id,
                'name' => (string) ($template->business_name ?: $template->name),
            ])
            ->values();

        $statuses = DB::table('template_cta_submissions as submissions')
            ->join('templates', 'templates.id', '=', 'submissions.template_id')
            ->where('templates.tenant_id', $tenantId)
            ->distinct()
            ->orderBy('submissions.status')
            ->pluck('submissions.status')
            ->filter()
            ->values();

        return [
            'event_types' => [
                ['value' => 'website_visit', 'label' => 'Website Visit'],
                ['value' => 'cta_view', 'label' => 'CTA View'],
                ['value' => 'cta_click', 'label' => 'CTA Click'],
                ['value' => 'form_submission', 'label' => 'Form Submission'],
                ['value' => 'conversion', 'label' => 'Conversion'],
            ],
            'templates'           => $templates,
            'conversion_statuses' => Collection::make(['success'])
                ->merge($statuses)
                ->unique()
                ->map(fn (string $status): array => ['value' => $status, 'label' => $this->titleText($status)])
                ->values(),
        ];
    }

    /**
     * @return array<string, int|null>
     */
    private function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'from'         => $paginator->firstItem(),
            'to'           => $paginator->lastItem(),
        ];
    }
}
