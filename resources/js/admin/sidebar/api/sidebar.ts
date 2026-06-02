import http from '@/shared/api/http'
import type { SidebarParams, SidebarPayload, SidebarRecord } from '@/shared/types/sidebar'

interface SidebarCollectionResponse {
  data: SidebarRecord[]
  pagination?: {
    total: number
  }
}

interface SidebarResponse {
  data: SidebarRecord
}

export const adminSidebarService = {
  index(params: SidebarParams = {}) {
    return http
      .get<SidebarCollectionResponse>('admin/sidebars', { params })
      .then((response) => response.data)
  },

  show(id: string) {
    return http.get<SidebarResponse>(`admin/sidebars/${id}`).then((response) => response.data)
  },

  update(id: string, payload: SidebarPayload) {
    return http
      .put<SidebarResponse>(`admin/sidebars/${id}`, payload)
      .then((response) => response.data)
  },
}
