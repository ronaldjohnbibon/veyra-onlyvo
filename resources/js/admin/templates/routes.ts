import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/admin/templates',
    name: 'admin.templates.index',
    component: () => import('@/admin/templates/pages/AdminTemplateMaintenancePage.vue'),
    meta: {
      requiresAdminAuth: true,
      layout: 'admin',
      title: 'Template Maintenance',
      description: 'Manage website types and complete template catalog entries',
    },
  },
]

export default routes
