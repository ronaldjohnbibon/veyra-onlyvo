import http from '@/shared/api/http'
import type { TenantDashboard } from '@/tenant/dashboard/types'

interface TenantDashboardResponse {
  data: TenantDashboard
}

export const tenantDashboardService = {
  index() {
    return http.get<TenantDashboardResponse>('dashboard').then((response) => response.data)
  },
}
