import type { DesignRequestStatus } from '@/shared/types/design-requests'
import type { PostStatus } from '@/shared/types/posts'
import type { TemplateStatus } from '@/shared/types/templates'

export interface TenantDashboardSite {
  name: string
  status: 'draft' | 'live' | string
  public_url: string | null
  tracking_enabled: boolean
  cta_forms_enabled: boolean
}

export interface TenantDashboardTemplate {
  id: string
  name: string
  business_name: string
  slug: string
  status: TemplateStatus
  is_default: boolean
  public_url: string | null
  website_type_name?: string | null
  updated_at?: string | null
}

export interface TenantDashboardMetrics {
  recent_visits: number
  today_visits: number
  recent_cta_events: number
  today_cta_events: number
  new_leads: number
  recent_leads: number
  published_posts: number
  draft_posts: number
  pending_design_requests: number
}

export interface TenantDashboardPost {
  id: string
  template_id: string
  template_name?: string | null
  site_slug?: string | null
  title: string
  slug: string
  status: PostStatus
  public_url?: string | null
  published_at?: string | null
  updated_at?: string | null
}

export interface TenantDashboardLead {
  id: string
  cta_type: string
  status: string
  summary: string
  template_name: string
  created_at?: string | null
}

export interface TenantDashboardDesignRequest {
  id: string
  title: string
  status: DesignRequestStatus
  created_at?: string | null
  updated_at?: string | null
}

export interface TenantDashboardChecklistItem {
  key: string
  label: string
  description: string
  completed: boolean
  to: string
}

export interface TenantDashboardModuleStatus {
  analytics: boolean
  posts: boolean
  design_requests: boolean
  cta_forms: boolean
}

export interface TenantDashboard {
  site: TenantDashboardSite
  default_template: TenantDashboardTemplate | null
  metrics: TenantDashboardMetrics
  recent_posts: TenantDashboardPost[]
  recent_leads: TenantDashboardLead[]
  pending_design_requests: TenantDashboardDesignRequest[]
  launch_checklist: TenantDashboardChecklistItem[]
  module_status: TenantDashboardModuleStatus
}
