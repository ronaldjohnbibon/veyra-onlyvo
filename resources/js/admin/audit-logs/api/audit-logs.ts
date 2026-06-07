import http from '@/shared/api/http'
import type { AuditLogPagination, AuditLogParams, AuditLogRecord } from '@/admin/audit-logs/types'

interface AuditLogsResponse {
  data: AuditLogRecord[]
  pagination?: AuditLogPagination
}

interface AuditLogResponse {
  data: AuditLogRecord
}

export const adminAuditLogService = {
  index(params: AuditLogParams = {}) {
    return http
      .get<AuditLogsResponse>('admin/audit-logs', { params })
      .then((response) => response.data)
  },

  show(id: string) {
    return http.get<AuditLogResponse>(`admin/audit-logs/${id}`).then((response) => response.data)
  },

  export(params: AuditLogParams = {}) {
    return http
      .get<Blob>('admin/audit-logs/export', {
        params,
        responseType: 'blob',
        headers: { 'X-Silent-Request': 'true' },
      })
      .then((response) => response.data)
  },
}
