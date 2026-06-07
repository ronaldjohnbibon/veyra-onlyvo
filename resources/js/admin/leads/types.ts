export type AdminLeadStatus = 'new' | 'contacted' | 'closed' | 'spam' | 'archived'

export interface AdminLead {
  id: string
  tenant_id: string | null
  tenant_name: string | null
  tenant_subdomain: string | null
  template_id: string
  template_name: string | null
  template_slug: string | null
  cta_type: string
  payload: Record<string, unknown>
  summary: string
  status: AdminLeadStatus
  links: {
    tenant: string | null
    public_site: string | null
    tracking_logs: string | null
    analytics: string | null
  }
  created_at: string | null
  updated_at: string | null
}

export interface AdminLeadParams {
  search?: string
  tenant_id?: string
  template_id?: string
  cta_type?: string
  status?: AdminLeadStatus | ''
  from?: string
  to?: string
  sort?: 'created_at' | 'status' | 'cta_type' | 'template' | 'tenant'
  direction?: 'asc' | 'desc' | ''
  page?: number
  pageSize?: number
}

export interface AdminLeadPagination {
  total: number
  per_page?: number
  current_page?: number
  last_page?: number
  from?: number | null
  to?: number | null
}

export interface AdminLeadOption {
  value: string
  label: string
}

export interface AdminLeadTenantOption {
  id: string
  name: string
  subdomain: string
}

export interface AdminLeadTemplateOption {
  id: string
  name: string
  tenant_id: string
  tenant_name?: string | null
  slug?: string | null
}

export interface AdminLeadSummary {
  total: number
  new: number
  contacted: number
  closed: number
  spam: number
  archived: number
  tenants: number
  templates: number
}

export interface AdminLeadTrend {
  date: string
  total: number
}

export interface AdminLeadConversions {
  contact_rate: number
  close_rate: number
  by_cta_type: {
    cta_type: string
    label: string
    total: number
  }[]
}

export interface AdminLeadMeta {
  filters: {
    tenants: AdminLeadTenantOption[]
    templates: AdminLeadTemplateOption[]
    cta_types: AdminLeadOption[]
    statuses: AdminLeadOption[]
  }
  summary: AdminLeadSummary
  trends: AdminLeadTrend[]
  conversions: AdminLeadConversions
}
