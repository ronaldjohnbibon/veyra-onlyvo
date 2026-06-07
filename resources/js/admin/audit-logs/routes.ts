import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/admin/audit-logs',
    name: 'admin.audit-logs.index',
    component: () => import('@/admin/audit-logs/pages/AdminAuditLogsPage.vue'),
    meta: {
      requiresAdminAuth: true,
      layout: 'admin',
      title: 'Audit Logs',
      description: 'Review centralized platform audit activity',
    },
  },
]

export default routes
