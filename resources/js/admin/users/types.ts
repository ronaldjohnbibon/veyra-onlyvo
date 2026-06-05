export interface AdminUserTokenActivity {
  id: number | string
  name: string
  abilities: string[]
  created_at: string | null
  last_used_at: string | null
  expires_at: string | null
  ip_address: string | null
  user_agent: string | null
  source: string
}

export interface AdminUserSecurityWarning {
  type: string
  level: 'critical' | 'warning' | 'info'
  message: string
}

export interface AdminUser {
  id: number
  tenant_id: string | null
  name: string
  first_name: string | null
  last_name: string | null
  email: string
  phone: string | null
  roles: string[]
  permissions: string[]
  user_type: string
  is_active: boolean
  email_verified: boolean
  two_factor_enabled: boolean
  ip_allow_list_enforced: boolean
  security_warnings: AdminUserSecurityWarning[]
  login_history: AdminUserTokenActivity[]
  access_logs: AdminUserTokenActivity[]
  tokens_count: number
  last_login_at: string | null
  created_at: string | null
  updated_at: string | null
}

export interface AdminUserPayload {
  name: string
  first_name: string
  last_name: string
  email: string
  phone: string
  is_active: boolean
  email_verified: boolean
  password: string
  password_confirmation: string
}

export interface AdminUserParams {
  page?: number
  pageSize?: number
  search?: string
  sort?: string
  direction?: 'asc' | 'desc' | ''
  status?: 'active' | 'inactive' | ''
}

export interface AdminUserPasswordReset {
  email: string
  reset_token: string
  expires_at: string
}
