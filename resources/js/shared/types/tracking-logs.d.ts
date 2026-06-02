import type { AnalyticsPagination } from '@/shared/types/analytics'

export type TrackingLogEventType =
  | ''
  | 'website_visit'
  | 'cta_view'
  | 'cta_click'
  | 'form_submission'
  | 'conversion'

export interface TrackingLogRecord {
  id: string
  source: string
  source_id: string
  event_type: Exclude<TrackingLogEventType, ''>
  event_type_label: string
  event_name: string
  tenant_id: string
  tenant_name: string
  template_id?: string | null
  template_name?: string | null
  website_template: string
  landing_page_url?: string | null
  visitor_identifier?: string | null
  session_identifier?: string | null
  referrer_url?: string | null
  utm_source?: string | null
  utm_medium?: string | null
  utm_campaign?: string | null
  device_type?: string | null
  browser?: string | null
  operating_system?: string | null
  ip_address?: string | null
  country_location?: string | null
  conversion_status?: string | null
  created_at: string
}

export interface TrackingLogOption {
  value?: string
  id?: string
  label?: string
  name?: string
}

export interface TrackingLogFilters {
  event_types: TrackingLogOption[]
  templates: TrackingLogOption[]
  conversion_statuses: TrackingLogOption[]
}

export interface TrackingLogParams {
  from?: string
  to?: string
  event_type?: TrackingLogEventType
  template_id?: string
  conversion_status?: string
  search?: string
  sort?: 'created_at' | 'event_type' | 'event_name' | 'tenant' | 'template'
  direction?: 'asc' | 'desc'
  page?: number
  pageSize?: number
}

export interface TrackingLogResponseData {
  data: TrackingLogRecord[]
  pagination: AnalyticsPagination
  filters: TrackingLogFilters
}
