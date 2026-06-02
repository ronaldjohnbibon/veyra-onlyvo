export type DesignRequestStatus = 'pending' | 'under_review' | 'approved' | 'rejected' | 'completed'

export interface DesignRequestFileRecord {
  id: string
  name: string
  url: string
  path: string
  mime_type?: string | null
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
  admin_remarks?: string | null
  files: DesignRequestFileRecord[]
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

export interface DesignRequestParams {
  direction?: 'asc' | 'desc'
  search?: string
  status?: DesignRequestStatus | ''
  tenant_id?: string
  page?: number
  pageSize?: number
  sort?: string
}
