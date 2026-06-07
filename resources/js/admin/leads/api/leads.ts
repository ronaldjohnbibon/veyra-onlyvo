import http from '@/shared/api/http'
import type {
  AdminLead,
  AdminLeadMeta,
  AdminLeadPagination,
  AdminLeadParams,
  AdminLeadStatus,
} from '@/admin/leads/types'

interface AdminLeadsResponse {
  data: AdminLead[]
  pagination?: AdminLeadPagination
  meta: AdminLeadMeta
}

interface AdminLeadResponse {
  data: AdminLead
}

export const adminLeadService = {
  index(params: AdminLeadParams = {}) {
    return http.get<AdminLeadsResponse>('admin/leads', { params }).then((response) => response.data)
  },

  updateStatus(id: string, status: AdminLeadStatus) {
    return http
      .put<AdminLeadResponse>(`admin/leads/${id}/status`, { status })
      .then((response) => response.data)
  },

  export(params: AdminLeadParams = {}) {
    return http
      .get<Blob>('admin/leads/export', {
        params,
        responseType: 'blob',
        headers: { 'X-Silent-Request': 'true' },
      })
      .then((response) => response.data)
  },
}
