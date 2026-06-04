import type { UserInterface } from '@/shared/types/user'

export interface AccountMember {
  id: number | string
  name: string
  email: string
  phone?: string | null
  role: string
  user_type?: string | null
  is_active: boolean
  is_owner: boolean
  permissions: string[]
}

export interface AccountTenant {
  id: string
  name: string
  subdomain: string
  settings?: Record<string, unknown> | null
  timezone?: string | null
  status?: string | null
}

export interface AccountWorkspace {
  tenant: AccountTenant | null
  owner_user_id?: number | string | null
  current_user_owner: boolean
  members: AccountMember[]
  member_count: number
  invites_supported: boolean
  roles_supported: boolean
  billing_supported: boolean
  permissions_summary: string
}

export interface AccountPayload {
  user: UserInterface
  workspace: AccountWorkspace
}

export interface AccountProfilePayload {
  name: string
  first_name?: string | null
  last_name?: string | null
  email: string
  phone?: string | null
}

export interface AccountPasswordPayload {
  current_password: string
  password: string
  password_confirmation: string
}
