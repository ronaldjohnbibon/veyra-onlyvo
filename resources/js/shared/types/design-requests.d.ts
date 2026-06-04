export type DesignRequestStatus =
  | 'pending'
  | 'under_review'
  | 'approved'
  | 'changes_requested'
  | 'rejected'
  | 'completed'

export type DesignRequestAction = 'approve' | 'request_changes'

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
  title: string
  description: string
  notes?: string | null
  reference_links: string[]
  mockup_concept?: string | null
  status: DesignRequestStatus
  status_label?: string
  status_explanation?: string
  admin_remarks?: string | null
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
  admin_remarks?: string | null
}

export interface DesignRequestCommentPayload {
  message: string
}

export interface DesignRequestActionPayload {
  action: DesignRequestAction
  message?: string | null
}

export interface DesignRequestParams {
  direction?: 'asc' | 'desc'
  search?: string
  status?: DesignRequestStatus | ''
  tenant_id?: string
  page?: number
  pageSize?: number
  sort?: string
}
