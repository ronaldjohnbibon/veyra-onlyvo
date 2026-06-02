export type TenantStatus = 'active' | 'inactive'

export interface TenantRecord {
  id: string
  name: string
  subdomain: string
  settings?: Record<string, unknown> | null
  timezone: string
  status: TenantStatus
  owner?: {
    id?: number | string | null
    name?: string | null
    first_name?: string | null
    last_name?: string | null
    email?: string | null
    phone?: string | null
  } | null
  users_count?: number
  templates_count?: number
  created_at?: string
  updated_at?: string
}

export interface TenantPayload {
  name: string
  subdomain: string
  timezone: string
  status: TenantStatus
  settings: Record<string, unknown>
  owner_name?: string
  owner_first_name?: string
  owner_last_name?: string
  owner_email?: string
  owner_phone?: string
  owner_password?: string
  owner_password_confirmation?: string
}

export interface TenantParams {
  direction?: 'asc' | 'desc' | ''
  page?: number
  pageSize?: number
  search?: string
  sort?: string
  status?: TenantStatus | ''
}
