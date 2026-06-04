import { defineStore } from 'pinia'
import { ref } from 'vue'
import { tenantDashboardService } from '@/tenant/dashboard/api/dashboard'
import type { TenantDashboard } from '@/tenant/dashboard/types'

export const useTenantDashboardStore = defineStore('tenant-dashboard', () => {
  const dashboard = ref<TenantDashboard | null>(null)
  const loading = ref(false)

  const index = async (): Promise<void> => {
    try {
      loading.value = true
      dashboard.value = (await tenantDashboardService.index()).data
    } finally {
      loading.value = false
    }
  }

  return {
    dashboard,
    index,
    loading,
  }
})
