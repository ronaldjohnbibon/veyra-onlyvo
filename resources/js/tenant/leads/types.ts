export type LeadStatus = 'new' | 'contacted' | 'closed' | 'spam' | 'archived'
export type LeadEditableStatus = Extract<LeadStatus, 'new' | 'contacted' | 'archived'>

export interface LeadRecord {
  id: string
  template_id: string
  template_name?: string | null
  template_slug?: string | null
  template_url?: string | null
  cta_type: string
  payload: Record<string, unknown>
  summary: string
  status: LeadStatus
  created_at?: string | null
  updated_at?: string | null
}

export interface LeadParams {
  search?: string
  status?: LeadStatus | ''
  template_id?: string
  cta_type?: string
  from?: string
  to?: string
  sort?: 'created_at' | 'status' | 'cta_type' | 'template'
  direction?: 'asc' | 'desc' | ''
  page?: number
  pageSize?: number
}

export interface LeadPagination {
  total: number
  per_page?: number
  current_page?: number
  last_page?: number
  from?: number | null
  to?: number | null
}

export interface LeadFilterOption {
  value: string
  label: string
}

export interface LeadTemplateOption {
  id: string
  name: string
}

export interface LeadFilters {
  templates: LeadTemplateOption[]
  cta_types: LeadFilterOption[]
  statuses: LeadFilterOption[]
}

export interface LeadStatusCounts {
  new: number
  contacted: number
  closed: number
  spam: number
  archived: number
}

export interface LeadMeta {
  status_counts: LeadStatusCounts
  filters: LeadFilters
}
