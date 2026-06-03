<?php

namespace App\Tenant\Dashboard\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dashboard(string $tenantId, array $filters): array
    {
        [$startDate, $endDate] = $this->dateRange($filters);

        return [
            'summary' => $this->summary($tenantId),
            'range'   => [
                'from' => $startDate->toDateString(),
                'to'   => $endDate->toDateString(),
            ],
            'daily_visits'       => $this->dailySeries('visitor_visits', 'visit_date', $tenantId, $startDate, $endDate),
            'daily_uniques'      => $this->dailySeries('visitor_unique_visitors', 'visit_date', $tenantId, $startDate, $endDate),
            'daily_cta_events'   => $this->dailySeries('cta_events', 'event_date', $tenantId, $startDate, $endDate),
            'cta_events_by_type' => $this->ctaEventsByType($tenantId, $startDate, $endDate),
            'top_ctas'           => $this->paginatedCtaTotals($tenantId, $startDate, $endDate, $filters),
            'top_pages'          => $this->paginatedTotals('visitor_visits', 'url', $tenantId, $startDate, $endDate, $filters, 'top_pages_page'),
            'top_referrers'      => $this->paginatedTotals('visitor_visits', 'referrer', $tenantId, $startDate, $endDate, $filters, 'referrers_page', true),
            'conversions'        => $this->conversions($tenantId, $startDate, $endDate),
        ];
    }

    public function pruneExpired(string $tenantId, int $retentionDays): void
    {
        $cutoff = now()->subDays(max(1, $retentionDays))->toDateString();

        DB::table('visitor_visits')
            ->where('tenant_id', $tenantId)
            ->where('visit_date', '<', $cutoff)
            ->delete();

        DB::table('visitor_unique_visitors')
            ->where('tenant_id', $tenantId)
            ->where('visit_date', '<', $cutoff)
            ->delete();

        DB::table('cta_events')
            ->where('tenant_id', $tenantId)
            ->where('event_date', '<', $cutoff)
            ->delete();

        DB::table('cta_unique_visitors')
            ->where('tenant_id', $tenantId)
            ->where('event_date', '<', $cutoff)
            ->delete();
    }

    /**
     * @return array<string, int>
     */
    private function summary(string $tenantId): array
    {
        $today       = now()->toDateString();
        $sevenDays   = now()->subDays(6)->toDateString();
        $thirtyDays  = now()->subDays(29)->toDateString();
        $visits      = DB::table('visitor_visits')->where('tenant_id', $tenantId);
        $uniqueViews = DB::table('visitor_unique_visitors')->where('tenant_id', $tenantId);
        $ctaEvents   = DB::table('cta_events')->where('tenant_id', $tenantId);
        $uniqueCta   = DB::table('cta_unique_visitors')->where('tenant_id', $tenantId);

        return [
            'total_visits'            => (clone $visits)->count(),
            'unique_visitors'         => (clone $uniqueViews)->count(),
            'today_visits'            => (clone $visits)->where('visit_date', $today)->count(),
            'today_unique_visitors'   => (clone $uniqueViews)->where('visit_date', $today)->count(),
            'last_7_days_visits'      => (clone $visits)->where('visit_date', '>=', $sevenDays)->count(),
            'last_30_days_visits'     => (clone $visits)->where('visit_date', '>=', $thirtyDays)->count(),
            'total_cta_events'        => (clone $ctaEvents)->count(),
            'unique_cta_visitors'     => (clone $uniqueCta)->count(),
            'today_cta_events'        => (clone $ctaEvents)->where('event_date', $today)->count(),
            'last_7_days_cta_events'  => (clone $ctaEvents)->where('event_date', '>=', $sevenDays)->count(),
            'last_30_days_cta_events' => (clone $ctaEvents)->where('event_date', '>=', $thirtyDays)->count(),
        ];
    }

    /**
     * @return array<int, array{date: string, total: int}>
     */
    private function dailySeries(string $table, string $dateColumn, string $tenantId, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $totals = DB::table($table)
            ->selectRaw($dateColumn.', count(*) as total')
            ->where('tenant_id', $tenantId)
            ->whereBetween($dateColumn, [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy($dateColumn)
            ->pluck('total', $dateColumn);

        return collect(CarbonPeriod::create($startDate, $endDate))
            ->map(fn ($date): array => [
                'date'  => $date->toDateString(),
                'total' => (int) ($totals[$date->toDateString()] ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{event_type: string, total: int}>
     */
    private function ctaEventsByType(string $tenantId, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return DB::table('cta_events')
            ->selectRaw('event_type, count(*) as total')
            ->where('tenant_id', $tenantId)
            ->whereBetween('event_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('event_type')
            ->orderByDesc('total')
            ->orderBy('event_type')
            ->get()
            ->map(fn (object $row): array => [
                'event_type' => (string) $row->event_type,
                'total'      => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function paginatedCtaTotals(string $tenantId, CarbonImmutable $startDate, CarbonImmutable $endDate, array $filters): array
    {
        $query = DB::table('cta_events')
            ->selectRaw('cta_identifier, cta_label, cta_type, count(*) as total')
            ->where('tenant_id', $tenantId)
            ->whereBetween('event_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('cta_identifier', 'cta_label', 'cta_type')
            ->orderByDesc('total')
            ->orderBy('cta_label');

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate(
            (int) ($filters['pageSize'] ?? 10),
            ['*'],
            'top_ctas_page',
            (int) ($filters['top_ctas_page'] ?? 1),
        );

        return [
            'data' => $paginator->getCollection()
                ->map(fn (object $row): array => [
                    'cta_identifier' => (string) $row->cta_identifier,
                    'cta_label'      => (string) ($row->cta_label ?: $row->cta_identifier),
                    'cta_type'       => (string) $row->cta_type,
                    'total'          => (int) $row->total,
                ])
                ->values(),
            'pagination' => $this->pagination($paginator),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function conversions(string $tenantId, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $visitRange  = [$startDate->toDateString(), $endDate->toDateString()];
        $eventRange  = [$startDate->toDateString(), $endDate->toDateString()];
        $totalVisits = DB::table('visitor_visits')
            ->where('tenant_id', $tenantId)
            ->whereBetween('visit_date', $visitRange)
            ->count();
        $totalCtaEvents = DB::table('cta_events')
            ->where('tenant_id', $tenantId)
            ->whereBetween('event_date', $eventRange)
            ->count();

        return [
            'total_visits'            => $totalVisits,
            'total_cta_events'        => $totalCtaEvents,
            'overall_conversion_rate' => $this->conversionRate($totalCtaEvents, $totalVisits),
            'per_cta'                 => $this->conversionRatePerCta($tenantId, $startDate, $endDate, $totalVisits),
            'per_page'                => $this->conversionRatePerPage($tenantId, $startDate, $endDate),
        ];
    }

    /**
     * @return array<int, array{cta_identifier: string, cta_label: string, cta_type: string, total_events: int, conversion_rate: float}>
     */
    private function conversionRatePerCta(string $tenantId, CarbonImmutable $startDate, CarbonImmutable $endDate, int $totalVisits): array
    {
        return DB::table('cta_events')
            ->selectRaw('cta_identifier, cta_label, cta_type, count(*) as total_events')
            ->where('tenant_id', $tenantId)
            ->whereBetween('event_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('cta_identifier', 'cta_label', 'cta_type')
            ->orderByDesc('total_events')
            ->limit(10)
            ->get()
            ->map(fn (object $row): array => [
                'cta_identifier'  => (string) $row->cta_identifier,
                'cta_label'       => (string) ($row->cta_label ?: $row->cta_identifier),
                'cta_type'        => (string) $row->cta_type,
                'total_events'    => (int) $row->total_events,
                'conversion_rate' => $this->conversionRate((int) $row->total_events, $totalVisits),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{url: string, total_visits: int, total_events: int, conversion_rate: float}>
     */
    private function conversionRatePerPage(string $tenantId, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $eventRows = DB::table('cta_events')
            ->selectRaw('url, count(*) as total_events')
            ->where('tenant_id', $tenantId)
            ->whereBetween('event_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('url')
            ->orderByDesc('total_events')
            ->limit(10)
            ->get();
        $urls        = $eventRows->pluck('url')->filter()->values();
        $visitTotals = $urls->isEmpty()
            ? collect()
            : DB::table('visitor_visits')
                ->selectRaw('url, count(*) as total_visits')
                ->where('tenant_id', $tenantId)
                ->whereIn('url', $urls)
                ->whereBetween('visit_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->groupBy('url')
                ->pluck('total_visits', 'url');

        return $eventRows
            ->map(function (object $row) use ($visitTotals): array {
                $totalVisits = (int) ($visitTotals[$row->url] ?? 0);
                $totalEvents = (int) $row->total_events;

                return [
                    'url'             => (string) $row->url,
                    'total_visits'    => $totalVisits,
                    'total_events'    => $totalEvents,
                    'conversion_rate' => $this->conversionRate($totalEvents, $totalVisits),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function paginatedTotals(
        string $table,
        string $column,
        string $tenantId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        array $filters,
        string $pageName,
        bool $skipBlank = false,
    ): array {
        $query = DB::table($table)
            ->selectRaw($column.' as value, count(*) as total')
            ->where('tenant_id', $tenantId)
            ->whereBetween('visit_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($skipBlank, fn ($query) => $query->whereNotNull($column)->where($column, '!=', ''))
            ->groupBy($column)
            ->orderByDesc('total')
            ->orderBy($column);

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate(
            (int) ($filters['pageSize'] ?? 10),
            ['*'],
            $pageName,
            (int) ($filters[$pageName] ?? 1),
        );

        return [
            'data' => $paginator->getCollection()
                ->map(fn (object $row): array => [
                    'value' => (string) $row->value,
                    'total' => (int) $row->total,
                ])
                ->values(),
            'pagination' => $this->pagination($paginator),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function dateRange(array $filters): array
    {
        $period = (string) ($filters['period'] ?? 'last_7_days');
        $today  = CarbonImmutable::today();

        if ($period === 'today') {
            return [$today, $today];
        }

        if ($period === 'last_30_days') {
            return [$today->subDays(29), $today];
        }

        if ($period === 'custom' && ! empty($filters['from']) && ! empty($filters['to'])) {
            $from = CarbonImmutable::parse((string) $filters['from'])->startOfDay();
            $to   = CarbonImmutable::parse((string) $filters['to'])->startOfDay();

            return $from->greaterThan($to) ? [$to, $from] : [$from, $to];
        }

        return [$today->subDays(6), $today];
    }

    private function conversionRate(int $events, int $visits): float
    {
        if ($visits <= 0) {
            return 0.0;
        }

        return round(($events / $visits) * 100, 2);
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
