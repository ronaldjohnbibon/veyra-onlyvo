import http from '@/shared/api/http'
import type {
  AnalyticsDashboard,
  AnalyticsParams,
  CtaTrackingPayload,
} from '@/shared/types/analytics'

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

  trackCtaEvent(payload: CtaTrackingPayload) {
    if (navigator.sendBeacon) {
      const baseUrl = (import.meta.env.VITE_API_URL || '/api').replace(/\/$/, '')
      const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' })

      if (navigator.sendBeacon(`${baseUrl}/public/analytics/cta-events`, blob)) {
        return Promise.resolve()
      }
    }

    return http
      .post('public/analytics/cta-events', payload, {
        headers: { 'X-Silent-Request': 'true' },
      })
      .then((response) => response.data)
  },
}
