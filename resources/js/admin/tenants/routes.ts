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
  {
    path: '/admin/tenants/:tenantId',
    name: 'admin.tenants.show',
    component: () => import('@/admin/tenants/pages/AdminTenantWorkspacePage.vue'),
    meta: {
      requiresAdminAuth: true,
      layout: 'admin',
      title: 'Tenant Workspace',
      description: 'Review tenant operations, activity, and health',
    },
  },
]

export default routes
