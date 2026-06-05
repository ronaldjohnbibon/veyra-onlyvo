import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/admin/admin-users',
    name: 'admin.users.index',
    component: () => import('@/admin/users/pages/AdminUserManagementPage.vue'),
    meta: {
      requiresAdminAuth: true,
      layout: 'admin',
      title: 'Admin Users',
      description: 'Manage platform administrator access',
    },
  },
]

export default routes
