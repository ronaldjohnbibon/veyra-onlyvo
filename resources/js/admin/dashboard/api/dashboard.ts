import http from '@/shared/api/http'
import type { AdminDashboard } from '@/admin/dashboard/types'

interface AdminDashboardResponse {
  data: AdminDashboard
}

export const adminDashboardService = {
  show() {
    return http.get<AdminDashboardResponse>('admin/dashboard').then((response) => response.data)
  },
}
