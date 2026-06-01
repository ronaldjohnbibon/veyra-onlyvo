<?php

namespace App\Modules\Analytics\Services;

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
            'daily_visits'  => $this->dailySeries('visitor_visits', $tenantId, $startDate, $endDate),
            'daily_uniques' => $this->dailySeries('visitor_unique_visitors', $tenantId, $startDate, $endDate),
            'top_pages'     => $this->paginatedTotals('visitor_visits', 'url', $tenantId, $startDate, $endDate, $filters, 'top_pages_page'),
            'top_referrers' => $this->paginatedTotals('visitor_visits', 'referrer', $tenantId, $startDate, $endDate, $filters, 'referrers_page', true),
        ];
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

        return [
            'total_visits'          => (clone $visits)->count(),
            'unique_visitors'       => (clone $uniqueViews)->count(),
            'today_visits'          => (clone $visits)->where('visit_date', $today)->count(),
            'today_unique_visitors' => (clone $uniqueViews)->where('visit_date', $today)->count(),
            'last_7_days_visits'    => (clone $visits)->where('visit_date', '>=', $sevenDays)->count(),
            'last_30_days_visits'   => (clone $visits)->where('visit_date', '>=', $thirtyDays)->count(),
        ];
    }

    /**
     * @return array<int, array{date: string, total: int}>
     */
    private function dailySeries(string $table, string $tenantId, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $totals = DB::table($table)
            ->selectRaw('visit_date, count(*) as total')
            ->where('tenant_id', $tenantId)
            ->whereBetween('visit_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('visit_date')
            ->pluck('total', 'visit_date');

        return collect(CarbonPeriod::create($startDate, $endDate))
            ->map(fn ($date): array => [
                'date'  => $date->toDateString(),
                'total' => (int) ($totals[$date->toDateString()] ?? 0),
            ])
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
