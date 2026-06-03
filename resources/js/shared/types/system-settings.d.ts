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
  previous_value: SystemSettingValue
  new_value: SystemSettingValue
  changed_by: {
    id: number | string | null
    name: string | null
    email: string | null
  }
  changed_at: string | null
}

export interface SystemSettingsValues {
  [key: string]: SystemSettingValue
}

export interface SystemSettingsPayload {
  [group: string]: Record<string, SystemSettingValue>
}
