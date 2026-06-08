import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/admin/logs',
    name: 'admin.logs.index',
    component: () => import('@/admin/audit-logs/pages/AdminAuditLogsPage.vue'),
    meta: {
      requiresAdminAuth: true,
      layout: 'admin',
      title: 'Audit Logs',
      description: 'Review centralized platform audit activity',
    },
  },
  {
    path: '/admin/audit-logs',
    redirect: { name: 'admin.logs.index' },
  },
]

export default routes
