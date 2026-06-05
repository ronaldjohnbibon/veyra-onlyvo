import { defineStore } from 'pinia'
import { ref } from 'vue'
import { adminDashboardService } from '@/admin/dashboard/api/dashboard'
import type { AdminDashboard } from '@/admin/dashboard/types'

export const useAdminDashboardStore = defineStore('admin-dashboard', () => {
  const dashboard = ref<AdminDashboard | null>(null)
  const loading = ref(false)

  const show = async (): Promise<void> => {
    try {
      loading.value = true
      dashboard.value = (await adminDashboardService.show()).data
    } finally {
      loading.value = false
    }
  }

  return {
    dashboard,
    loading,
    show,
  }
})
