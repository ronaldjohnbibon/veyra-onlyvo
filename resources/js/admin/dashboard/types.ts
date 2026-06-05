export type DashboardHealthStatus = 'healthy' | 'warning' | 'critical'

export interface AdminDashboardMetrics {
  total_tenants: number
  active_tenants: number
  inactive_tenants: number
  new_tenants_this_week: number
  pending_design_requests: number
  new_leads: number
  published_templates: number
}

export interface AdminDashboardHealthItem {
  key: string
  label: string
  status: DashboardHealthStatus
  value: string
  description: string
}

export interface AdminDashboardHealth {
  status: DashboardHealthStatus
  summary: string
  items: AdminDashboardHealthItem[]
}

export interface AdminDashboardDesignRequest {
  id: string
  title: string
  status: string
  tenant_name?: string | null
  created_at?: string | null
}

export interface AdminDashboardLead {
  id: string
  cta_type: string
  status: string
  template_name?: string | null
  tenant_name?: string | null
  created_at?: string | null
}

export interface AdminDashboardPendingWork {
  pending_design_requests: number
  under_review_requests: number
  changes_requested: number
  new_leads: number
  oldest_pending_request: AdminDashboardDesignRequest | null
  recent_design_requests: AdminDashboardDesignRequest[]
  recent_leads: AdminDashboardLead[]
}

export interface AdminDashboardTenantActivity {
  id: string
  name: string
  subdomain: string
  status: string
  users_count: number
  templates_count: number
  activity_label: string
  created_at?: string | null
  updated_at?: string | null
}

export interface AdminDashboard {
  metrics: AdminDashboardMetrics
  pending_work: AdminDashboardPendingWork
  recent_tenant_activity: AdminDashboardTenantActivity[]
  platform_health: AdminDashboardHealth
  generated_at: string
}
