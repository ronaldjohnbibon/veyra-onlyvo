import http from '@/shared/api/http'
import type { TrackingLogParams, TrackingLogResponseData } from '@/shared/types/tracking-logs'

interface TrackingLogResponse {
  data: TrackingLogResponseData
}

export const trackingLogService = {
  index(params: TrackingLogParams = {}) {
    return http
      .get<TrackingLogResponse>('tracking-logs', { params })
      .then((response) => response.data)
  },

  exportCsv(params: TrackingLogParams = {}) {
    return http
      .get<Blob>('tracking-logs/export', {
        params,
        responseType: 'blob',
      })
      .then((response) => response.data)
  },
}
