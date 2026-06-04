import { defineStore } from 'pinia'
import { ref } from 'vue'
import { trackingLogService } from '@/tenant/tracking-logs/api/tracking-logs'
import type {
  TrackingLogFilters,
  TrackingLogParams,
  TrackingLogRecord,
} from '@/shared/types/tracking-logs'
import type { AnalyticsPagination } from '@/shared/types/analytics'

export const useTrackingLogStore = defineStore('tracking-logs', () => {
  const logs = ref<TrackingLogRecord[]>([])
  const loading = ref(false)
  const pagination = ref<AnalyticsPagination | null>(null)
  const total = ref(0)
  const filters = ref<TrackingLogFilters>({
    event_types: [],
    templates: [],
    conversion_statuses: [],
  })
  const params = ref<TrackingLogParams>({
    page: 1,
    pageSize: 15,
    sort: 'created_at',
    direction: 'desc',
    search: '',
    event_type: '',
    template_id: '',
    conversion_status: '',
  })

  const index = async (newParams: Partial<TrackingLogParams> = {}): Promise<void> => {
    try {
      loading.value = true
      params.value = { ...params.value, ...newParams }

      const response = await trackingLogService.index(params.value)
      logs.value = response.data.data ?? []
      pagination.value = response.data.pagination
      total.value = response.data.pagination?.total ?? logs.value.length
      filters.value = response.data.filters
    } finally {
      loading.value = false
    }
  }

  const reset = async (): Promise<void> => {
    // Reset keeps the current page size while clearing table filters.
    await index({
      from: '',
      to: '',
      event_type: '',
      template_id: '',
      conversion_status: '',
      search: '',
      sort: 'created_at',
      direction: 'desc',
      page: 1,
    })
  }

  const exportCsv = async (): Promise<void> => {
    const blob = await trackingLogService.exportCsv(params.value)
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')

    link.href = url
    link.download = 'tracking-logs.csv'
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  }

  return {
    exportCsv,
    filters,
    index,
    loading,
    logs,
    pagination,
    params,
    reset,
    total,
  }
})
