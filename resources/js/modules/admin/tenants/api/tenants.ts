import http from '@/shared/api/http'
import type { TenantParams, TenantPayload, TenantRecord } from '@/types/tenants'

interface TenantCollectionResponse {
  data: TenantRecord[]
  pagination?: {
    total: number
  }
}

interface TenantResponse {
  data: TenantRecord
}

export const adminTenantService = {
  index(params: TenantParams = {}) {
    return http
      .get<TenantCollectionResponse>('admin/tenants', { params })
      .then((response) => response.data)
  },

  store(payload: TenantPayload) {
    return http.post<TenantResponse>('admin/tenants', payload).then((response) => response.data)
  },

  update(id: string, payload: TenantPayload) {
    return http
      .put<TenantResponse>(`admin/tenants/${id}`, payload)
      .then((response) => response.data)
  },

  deactivate(id: string) {
    return http
      .post<TenantResponse>(`admin/tenants/${id}/deactivate`)
      .then((response) => response.data)
  },

  reactivate(id: string) {
    return http
      .post<TenantResponse>(`admin/tenants/${id}/reactivate`)
      .then((response) => response.data)
  },

  destroy(id: string) {
    return http.delete(`admin/tenants/${id}`).then((response) => response.data)
  },
}
