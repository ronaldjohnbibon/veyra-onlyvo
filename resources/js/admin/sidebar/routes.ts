import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/admin/navigation-builder',
    name: 'admin.sidebar.index',
    component: () => import('@/admin/sidebar/pages/AdminSidebarSettingsPage.vue'),
    meta: {
      requiresAdminAuth: true,
      layout: 'admin',
      title: 'Navigation Builder',
      description: 'Build the admin sidebar navigation',
    },
  },
]

export default routes
