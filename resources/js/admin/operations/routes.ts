import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/admin/operations',
    name: 'admin.operations.index',
    component: () => import('@/admin/operations/pages/AdminOperationsPage.vue'),
    meta: {
      requiresAdminAuth: true,
      layout: 'admin',
      title: 'Platform Operations',
      description: 'Monitor platform health, queues, storage, mail, analytics, and errors',
    },
  },
]

export default routes
