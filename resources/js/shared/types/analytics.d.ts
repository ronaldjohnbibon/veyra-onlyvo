export type AnalyticsPeriod = 'today' | 'last_7_days' | 'last_30_days' | 'custom'

export interface AnalyticsSummary {
  total_visits: number
  unique_visitors: number
  today_visits: number
  today_unique_visitors: number
  last_7_days_visits: number
  last_30_days_visits: number
  total_cta_events: number
  unique_cta_visitors: number
  today_cta_events: number
  last_7_days_cta_events: number
  last_30_days_cta_events: number
}

export interface AnalyticsDailyTotal {
  date: string
  total: number
}

export interface AnalyticsTotalRow {
  value: string
  total: number
}

export interface AnalyticsCtaTotalRow {
  cta_identifier: string
  cta_label: string
  cta_type: string
  total: number
}

export interface AnalyticsEventTypeRow {
  event_type: string
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

export interface AnalyticsPagedCtaTotals {
  data: AnalyticsCtaTotalRow[]
  pagination: AnalyticsPagination
}

export interface AnalyticsCtaConversionRow {
  cta_identifier: string
  cta_label: string
  cta_type: string
  total_events: number
  conversion_rate: number
}

export interface AnalyticsPageConversionRow {
  url: string
  total_visits: number
  total_events: number
  conversion_rate: number
}

export interface AnalyticsConversions {
  total_visits: number
  total_cta_events: number
  overall_conversion_rate: number
  per_cta: AnalyticsCtaConversionRow[]
  per_page: AnalyticsPageConversionRow[]
}

export interface AnalyticsDashboard {
  summary: AnalyticsSummary
  range: {
    from: string
    to: string
  }
  daily_visits: AnalyticsDailyTotal[]
  daily_uniques: AnalyticsDailyTotal[]
  daily_cta_events: AnalyticsDailyTotal[]
  cta_events_by_type: AnalyticsEventTypeRow[]
  top_ctas: AnalyticsPagedCtaTotals
  top_pages: AnalyticsPagedTotals
  top_referrers: AnalyticsPagedTotals
  conversions: AnalyticsConversions
}

export interface AnalyticsParams {
  period?: AnalyticsPeriod
  from?: string
  to?: string
  pageSize?: number
  top_pages_page?: number
  referrers_page?: number
  top_ctas_page?: number
}

export type CtaEventType =
  | 'cta_view'
  | 'button_click'
  | 'link_click'
  | 'form_opened'
  | 'form_submitted'
  | 'booking_submitted'
  | 'message_submitted'
  | 'quote_request_submitted'
  | 'newsletter_signup_submitted'
  | 'phone_click'
  | 'email_click'
  | 'whatsapp_click'
  | 'social_media_click'

export interface CtaTrackingPayload {
  template_id: string
  cta_identifier: string
  cta_label?: string | null
  cta_type: string
  event_type: CtaEventType
  url: string
  referrer?: string | null
}
