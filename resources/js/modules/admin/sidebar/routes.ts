import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/admin/sidebar',
    name: 'admin.sidebar.index',
    component: () => import('@/modules/admin/sidebar/pages/AdminSidebarSettingsPage.vue'),
    meta: {
      requiresAdminAuth: true,
      layout: 'admin',
      title: 'Admin Sidebar Settings',
      description: 'Manage admin sidebar navigation',
    },
  },
]

export default routes
