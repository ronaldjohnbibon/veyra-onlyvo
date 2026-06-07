import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/system-settings',
    name: 'tenant.system-settings.index',
    component: () => import('@/tenant/system-settings/pages/TenantSystemSettingsPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'System Settings',
      description:
        'Manage launch, SEO, branding, notifications, compliance, and analytics settings',
    },
  },
]

export default routes
