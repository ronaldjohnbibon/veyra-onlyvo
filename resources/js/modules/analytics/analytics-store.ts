import { defineStore } from 'pinia'
import { ref } from 'vue'
import { analyticsService } from '@/modules/analytics/api/analytics'
import type { AnalyticsDashboard, AnalyticsParams, AnalyticsPeriod } from '@/types/analytics'

export const useAnalyticsStore = defineStore('analytics', () => {
  const dashboard = ref<AnalyticsDashboard | null>(null)
  const loading = ref(false)
  const params = ref<AnalyticsParams>({
    period: 'last_7_days',
    pageSize: 10,
    top_pages_page: 1,
    referrers_page: 1,
  })
  const trackedRoutes = ref(new Set<string>())

  const index = async (newParams: Partial<AnalyticsParams> = {}): Promise<void> => {
    try {
      loading.value = true
      params.value = { ...params.value, ...newParams }
      dashboard.value = (await analyticsService.dashboard(params.value)).data
    } finally {
      loading.value = false
    }
  }

  const setPeriod = async (period: AnalyticsPeriod): Promise<void> => {
    const resetRange = period === 'custom' ? {} : { from: undefined, to: undefined }

    await index({
      ...resetRange,
      period,
      top_pages_page: 1,
      referrers_page: 1,
    })
  }

  const trackPublicVisit = async (templateId: string, url: string): Promise<void> => {
    const routeKey = `${templateId}:${url}`

    if (trackedRoutes.value.has(routeKey)) {
      return
    }

    trackedRoutes.value.add(routeKey)

    try {
      await analyticsService.trackVisit({
        template_id: templateId,
        url,
        referrer: document.referrer || null,
      })
    } catch {
      // Visitor tracking should never interrupt public site rendering.
    }
  }

  return {
    dashboard,
    index,
    loading,
    params,
    setPeriod,
    trackPublicVisit,
  }
})
