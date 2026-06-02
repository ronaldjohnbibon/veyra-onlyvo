import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/admin/tenants',
    name: 'admin.tenants.index',
    component: () => import('@/admin/tenants/pages/AdminTenantManagementPage.vue'),
    meta: {
      requiresAdminAuth: true,
      layout: 'admin',
      title: 'Tenant Management',
      description: 'Manage platform tenants',
    },
  },
]

export default routes
