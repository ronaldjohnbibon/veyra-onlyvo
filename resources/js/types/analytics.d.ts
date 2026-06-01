export type AnalyticsPeriod = 'today' | 'last_7_days' | 'last_30_days' | 'custom'

export interface AnalyticsSummary {
  total_visits: number
  unique_visitors: number
  today_visits: number
  today_unique_visitors: number
  last_7_days_visits: number
  last_30_days_visits: number
}

export interface AnalyticsDailyTotal {
  date: string
  total: number
}

export interface AnalyticsTotalRow {
  value: string
  total: number
}

export interface AnalyticsPagination {
  total: number
  per_page: number
  current_page: number
  last_page: number
  from?: number | null
  to?: number | null
}

export interface AnalyticsPagedTotals {
  data: AnalyticsTotalRow[]
  pagination: AnalyticsPagination
}

export interface AnalyticsDashboard {
  summary: AnalyticsSummary
  range: {
    from: string
    to: string
  }
  daily_visits: AnalyticsDailyTotal[]
  daily_uniques: AnalyticsDailyTotal[]
  top_pages: AnalyticsPagedTotals
  top_referrers: AnalyticsPagedTotals
}

export interface AnalyticsParams {
  period?: AnalyticsPeriod
  from?: string
  to?: string
  pageSize?: number
  top_pages_page?: number
  referrers_page?: number
}
