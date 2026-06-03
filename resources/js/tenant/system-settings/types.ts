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
  action: 'created' | 'updated' | 'deleted'
  previous_value: SystemSettingValue
  new_value: SystemSettingValue
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
