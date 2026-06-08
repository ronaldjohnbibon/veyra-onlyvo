import http from '@/shared/api/http'
import type { AuditLogPagination, AuditLogParams, AuditLogRecord } from '@/tenant/audit-logs/types'

interface AuditLogsResponse {
  data: AuditLogRecord[]
  pagination?: AuditLogPagination
}

interface AuditLogResponse {
  data: AuditLogRecord
}

export const tenantAuditLogService = {
  index(params: AuditLogParams = {}) {
    return http.get<AuditLogsResponse>('app/logs', { params }).then((response) => response.data)
  },

  show(id: string) {
    return http.get<AuditLogResponse>(`app/logs/${id}`).then((response) => response.data)
  },

  export(params: AuditLogParams = {}) {
    return http
      .get<Blob>('app/logs/export', {
        params,
        responseType: 'blob',
        headers: { 'X-Silent-Request': 'true' },
      })
      .then((response) => response.data)
  },
}
