export interface AuditLogActor {
  type: string
  id: string | null
  name: string | null
  email: string | null
}

export interface AuditLogEntity {
  type: string
  id: string | null
  label: string | null
}

export type AuditLogCategory =
  | 'activity'
  | 'auth'
  | 'audit'
  | 'error'
  | 'notification'
  | 'system'
  | 'file_upload'
  | 'permission'
  | 'transaction'

export type AuditLogSeverity = 'info' | 'warning' | 'error' | 'critical'

export interface AuditLogRecord {
  id: string
  scope: 'tenant'
  tenant_id: string
  category: AuditLogCategory
  severity: AuditLogSeverity
  actor: AuditLogActor
  ip_address: string | null
  user_agent: string | null
  entity: AuditLogEntity
  action: string
  summary: string
  previous_value: unknown
  new_value: unknown
  metadata: Record<string, unknown> | null
  occurred_at: string | null
  created_at: string | null
}

export interface AuditLogPagination {
  total: number
  per_page: number
  current_page: number
  last_page: number
  from?: number | null
  to?: number | null
}

export interface AuditLogParams {
  page?: number
  pageSize?: number
  search?: string
  category?: AuditLogCategory | ''
  action?: string
  severity?: AuditLogSeverity | ''
  actor?: string
  entity_type?: string
  entity_id?: string
  ip_address?: string
  date_from?: string
  date_to?: string
  sort?: 'occurred_at' | 'category' | 'severity' | 'actor' | 'entity' | 'action' | 'ip_address'
  direction?: 'asc' | 'desc'
}
