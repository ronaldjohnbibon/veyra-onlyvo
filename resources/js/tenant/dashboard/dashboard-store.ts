import { defineStore } from 'pinia'
import { ref } from 'vue'
import { apiMessageFrom } from '@/shared/api/errors'
import { tenantDashboardService } from '@/tenant/dashboard/api/dashboard'
import type { TenantDashboard } from '@/tenant/dashboard/types'

export const useTenantDashboardStore = defineStore('tenant-dashboard', () => {
  const dashboard = ref<TenantDashboard | null>(null)
  const error = ref('')
  const loaded = ref(false)
  const loading = ref(false)

  const index = async (): Promise<void> => {
    try {
      loading.value = true
      error.value = ''
      dashboard.value = (await tenantDashboardService.index()).data
    } catch (err) {
      dashboard.value = null
      error.value = apiMessageFrom(err, 'Dashboard unavailable.')
    } finally {
      loading.value = false
      loaded.value = true
    }
  }

  return {
    dashboard,
    error,
    index,
    loaded,
    loading,
  }
})
