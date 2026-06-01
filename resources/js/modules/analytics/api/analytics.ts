import http from '@/shared/api/http'
import type { AnalyticsDashboard, AnalyticsParams } from '@/types/analytics'

interface AnalyticsResponse {
  data: AnalyticsDashboard
}

interface TrackingPayload {
  template_id: string
  url: string
  referrer?: string | null
}

export const analyticsService = {
  dashboard(params: AnalyticsParams = {}) {
    return http.get<AnalyticsResponse>('analytics', { params }).then((response) => response.data)
  },

  trackVisit(payload: TrackingPayload) {
    return http
      .post('public/analytics/visits', payload, {
        headers: { 'X-Silent-Request': 'true' },
      })
      .then((response) => response.data)
  },
}
