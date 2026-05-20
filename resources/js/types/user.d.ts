export interface UserInterface {
  id?: number | string
  tenant_id?: string | null
  name?: string | null
  first_name?: string | null
  last_name?: string | null
  email: string
  phone?: string | null
  roles: string[]
  permissions: string[]
  user_type?: string
  is_active?: boolean
}
