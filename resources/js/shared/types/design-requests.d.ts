export type DesignRequestStatus =
  | 'pending'
  | 'under_review'
  | 'approved'
  | 'changes_requested'
  | 'rejected'
  | 'completed'

export type DesignRequestAction = 'approve' | 'request_changes'
export type DesignRequestPriority = 'low' | 'normal' | 'high' | 'urgent'

export interface DesignRequestEventRecord {
  id: string
  actor_type: 'tenant' | 'admin' | 'system' | string
  actor_name?: string | null
  event_type:
    | 'created'
    | 'comment'
    | 'status_changed'
    | 'feedback'
    | 'approval'
    | 'changes_requested'
    | 'assigned'
    | 'priority_changed'
    | 'converted_to_template_improvement'
    | 'converted_to_catalog_change'
    | 'linked_completed_work'
    | 'notification_marked'
    | string
  from_status?: DesignRequestStatus | null
  to_status?: DesignRequestStatus | null
  message?: string | null
  created_at?: string
}

export interface DesignRequestFileRecord {
  id: string
  name: string
  url: string
  path: string
  mime_type?: string | null
  is_image?: boolean
  size: number
  created_at?: string
}

export interface DesignRequestRecord {
  id: string
  tenant_id: string
  tenant_name?: string | null
  requester_name?: string | null
  assigned_to?: number | string | null
  assignee_name?: string | null
  title: string
  description: string
  notes?: string | null
  reference_links: string[]
  mockup_concept?: string | null
  status: DesignRequestStatus
  status_label?: string
  status_explanation?: string
  priority: DesignRequestPriority
  due_at?: string | null
  sla_due_at?: string | null
  sla?: {
    state: 'not_set' | 'met' | 'missed' | 'overdue' | 'due_soon' | 'on_track'
    label: string
    hours_left: number | null
  }
  admin_remarks?: string | null
  internal_notes?: string | null
  notification_requested?: boolean
  notification_sent_at?: string | null
  conversion_type?: 'template_improvement' | 'catalog_change' | string | null
  conversion_payload?: Record<string, unknown>
  converted_at?: string | null
  linked_template_id?: string | null
  linked_template_name?: string | null
  linked_site_url?: string | null
  files: DesignRequestFileRecord[]
  events?: DesignRequestEventRecord[]
  reviewed_at?: string | null
  completed_at?: string | null
  created_at?: string
  updated_at?: string
}

export interface DesignRequestPayload {
  title: string
  description: string
  notes?: string | null
  reference_links: string[]
  mockup_concept?: string | null
  files?: File[]
}

export interface AdminDesignRequestPayload {
  status: DesignRequestStatus
  assigned_to?: number | string | null
  priority: DesignRequestPriority
  due_at?: string | null
  sla_due_at?: string | null
  admin_remarks?: string | null
  internal_notes?: string | null
}

export interface DesignRequestCommentPayload {
  message: string
}

export interface DesignRequestActionPayload {
  action: DesignRequestAction
  message?: string | null
}

export interface DesignRequestConversionPayload {
  summary?: string | null
  changelog?: string | null
  target_key?: string | null
}

export interface DesignRequestLinkPayload {
  linked_template_id?: string | null
  linked_site_url?: string | null
}

export interface DesignRequestBoardColumn {
  status: DesignRequestStatus
  label: string
  items: DesignRequestRecord[]
}

export interface DesignRequestWorkloadAssignee {
  id: number | string
  name: string
  email: string
  assigned: number
  urgent: number
  overdue: number
  due_soon: number
}

export interface DesignRequestWorkload {
  assignees: DesignRequestWorkloadAssignee[]
  unassigned: number
}

export interface DesignRequestParams {
  direction?: 'asc' | 'desc'
  search?: string
  status?: DesignRequestStatus | ''
  priority?: DesignRequestPriority | ''
  assigned_to?: number | string | ''
  tenant_id?: string
  page?: number
  pageSize?: number
  sort?: string
}
