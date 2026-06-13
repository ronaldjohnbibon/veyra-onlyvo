export type SystemSettingValue = string | number | boolean | null

export type SystemSettingType =
  | 'string'
  | 'text'
  | 'boolean'
  | 'integer'
  | 'password'
  | 'email'
  | 'url'
  | 'color'
  | 'image'
  | 'select'

export interface SystemSettingItem {
  key: string
  name: string
  label: string
  type: SystemSettingType
  value: SystemSettingValue
  is_public: boolean
  is_sensitive?: boolean
  is_masked?: boolean
  description: string | null
  options: Record<string, string> | null
}

export interface SystemSettingGroup {
  key: string
  label: string
  settings: SystemSettingItem[]
}

export interface SystemSettingHistoryRecord {
  id: string
  setting_key: string
  action: 'created' | 'updated' | 'deleted' | 'restored' | 'tested' | 'exported' | 'backed_up'
  previous_value: SystemSettingValue
  new_value: SystemSettingValue
  can_restore: boolean
  changed_by: {
    id: number | string | null
    name: string | null
    email: string | null
  }
  changed_at: string | null
}

export interface SystemSettingHistoryPagination {
  total: number
  per_page?: number
  current_page?: number
  last_page?: number
  from?: number | null
  to?: number | null
}

export interface SystemSettingHistoryPayload {
  data: SystemSettingHistoryRecord[]
  pagination: SystemSettingHistoryPagination
}

export interface SystemSettingHistoryParams {
  setting_key?: string
  action?: string
  search?: string
  sort?: string
  direction?: 'asc' | 'desc' | ''
  page?: number
  pageSize?: number
}

export interface SystemSettingsValues {
  [key: string]: SystemSettingValue
}

export interface SystemSettingsPayload {
  [group: string]: Record<string, SystemSettingValue>
}

export interface SystemSettingSmtpTestResult {
  status: 'ok' | 'failed' | 'skipped'
  message: string
  driver?: string
  host?: string
  port?: number
  code?: number
  latency_ms?: number
}

export interface SystemSettingEmailTestResult {
  status: 'sent'
  recipient: string
  driver: string
  message: string
}

export interface SystemSettingMaintenancePreview {
  dry_run: boolean
  enabled: boolean
  active_now: boolean
  path: string
  path_affected: boolean
  admin_bypass: boolean
  message: string
  starts_at: string
  ends_at: string
  affected_areas: string[]
  affected_summary: string
  result: 'blocked' | 'allowed'
}
