import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/admin/system-settings',
    name: 'admin.system-settings.index',
    component: () => import('@/admin/system-settings/pages/AdminSystemSettingsPage.vue'),
    meta: {
      requiresAdminAuth: true,
      layout: 'admin',
      title: 'System Settings',
      description: 'Manage global platform settings',
    },
  },
]

export default routes
