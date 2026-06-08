import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/logs',
    name: 'tenant.logs.index',
    component: () => import('@/tenant/audit-logs/pages/TenantAuditLogsPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Audit Logs',
      description: 'Review workspace audit and activity events',
    },
  },
]

export default routes
