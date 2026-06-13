import http from '@/shared/api/http'
import type {
  LeadEditableStatus,
  LeadMeta,
  LeadPagination,
  LeadParams,
  LeadRecord,
} from '@/tenant/leads/types'

interface LeadsResponse {
  data: LeadRecord[]
  pagination?: LeadPagination
  meta?: LeadMeta
}

interface LeadResponse {
  data: LeadRecord
}

export const leadService = {
  index(params: LeadParams = {}) {
    return http.get<LeadsResponse>('leads', { params }).then((response) => response.data)
  },

  show(id: string) {
    return http.get<LeadResponse>(`leads/${id}`).then((response) => response.data)
  },

  updateStatus(id: string, status: LeadEditableStatus) {
    return http
      .put<LeadResponse>(`leads/${id}/status`, { status })
      .then((response) => response.data)
  },

  export(params: LeadParams = {}) {
    return http
      .get<Blob>('leads/export', {
        params,
        responseType: 'blob',
      })
      .then((response) => response.data)
  },
}
