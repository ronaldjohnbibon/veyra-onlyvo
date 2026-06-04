export interface SidebarParams {
  search?: string
  page?: number
  pageSize?: number
}

export interface SidebarTeam {
  name: string
  logo?: string
  plan?: string
}

export interface SidebarProject {
  name: string
  url: string
  icon?: string
}

export interface SidebarNavChild {
  title: string
  url: string
  icon?: string
  description?: string
  is_active?: boolean
}

export interface SidebarNavItem {
  title: string
  url: string
  icon?: string
  description?: string
  is_active?: boolean
  items?: SidebarNavChild[]
}

export interface SidebarData {
  teams?: SidebarTeam[]
  main_nav: SidebarNavItem[]
  projects?: SidebarProject[]
}

export interface SidebarRecord {
  id: string
  tenant_id?: string | null
  name: string
  description?: string | null
  is_admin: boolean
  data: SidebarData
  stats?: {
    nav_groups: number
    links: number
  }
  updated_at?: string
}

export interface SidebarPayload {
  name: string
  description?: string | null
  data: SidebarData
}
