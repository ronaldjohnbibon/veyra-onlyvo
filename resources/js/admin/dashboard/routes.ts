import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/admin/dashboard',
    name: 'admin.dashboard',
    component: () => import('@/admin/dashboard/pages/AdminDashboardPage.vue'),
    meta: {
      requiresAdminAuth: true,
      layout: 'admin',
      title: 'Admin Dashboard',
    },
  },
]

export default routes
