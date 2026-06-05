import type { TenantRecord } from '@/shared/types/tenants'

export interface TenantWorkspaceUser {
  id: number | string
  name: string
  first_name?: string | null
  last_name?: string | null
  email: string
  phone?: string | null
  user_type: string
  is_active: boolean
  email_verified_at?: string | null
  created_at?: string | null
}

export interface TenantWorkspaceDomain {
  subdomain: string
  workspace_url: string
  public_site_url?: string | null
  status: 'connected' | 'attention' | string
  status_label: string
  status_detail: string
}

export interface TenantWorkspaceTemplate {
  id: string
  name: string
  business_name: string
  slug: string
  template_key: string
  status: string
  is_default: boolean
  website_type_name?: string | null
  posts_count: number
  leads_count: number
  updated_at?: string | null
}

export interface TenantWorkspaceRecentItem {
  id: string
  title?: string
  slug?: string
  cta_type?: string
  summary?: string
  status: string
  template_name?: string | null
  business_name?: string | null
  created_at?: string | null
  updated_at?: string | null
  published_at?: string | null
}

export interface TenantWorkspacePostsSummary {
  total: number
  published: number
  draft: number
  recent: TenantWorkspaceRecentItem[]
}

export interface TenantWorkspaceLeadsSummary {
  total: number
  new: number
  contacted: number
  archived: number
  recent: TenantWorkspaceRecentItem[]
}

export interface TenantWorkspaceDesignRequestSummary {
  total: number
  pending: number
  under_review: number
  changes_requested: number
  completed: number
  recent: TenantWorkspaceRecentItem[]
}

export interface TenantWorkspaceTimelineItem {
  id: string
  type: string
  title: string
  description: string
  occurred_at?: string | null
}

export interface TenantWorkspaceAuditItem {
  id: string
  setting_key: string
  action: string
  changed_by_name?: string | null
  changed_by_email?: string | null
  changed_at?: string | null
}

export interface TenantWorkspaceUsageMetrics {
  users: number
  templates: number
  published_templates: number
  posts: number
  leads: number
  design_requests: number
  visits_last_7_days: number
  visits_today: number
  cta_events_last_7_days: number
  storage_bytes: number
  storage_label: string
}

export interface TenantWorkspaceFeatureOverride {
  key: string
  value: unknown
  source: string
}

export interface TenantWorkspacePlan {
  plan_name: string
  trial_days: number
  trial_ends_at?: string | null
  trial_status: 'active' | 'expired' | 'not_set' | string
}

export interface TenantWorkspaceActions {
  open_workspace: {
    available: boolean
    url?: string | null
  }
  impersonation: {
    available: boolean
    url?: string | null
    reason?: string | null
  }
}

export interface TenantWorkspace {
  tenant: TenantRecord
  owner: TenantWorkspaceUser | null
  users: TenantWorkspaceUser[]
  domain: TenantWorkspaceDomain
  published_template: TenantWorkspaceTemplate | null
  posts_summary: TenantWorkspacePostsSummary
  leads_summary: TenantWorkspaceLeadsSummary
  design_request_summary: TenantWorkspaceDesignRequestSummary
  activity_timeline: TenantWorkspaceTimelineItem[]
  audit_history: TenantWorkspaceAuditItem[]
  usage_metrics: TenantWorkspaceUsageMetrics
  feature_overrides: TenantWorkspaceFeatureOverride[]
  plan: TenantWorkspacePlan
  actions: TenantWorkspaceActions
  generated_at: string
}
