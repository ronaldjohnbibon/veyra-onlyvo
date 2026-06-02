import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/admin/login',
    name: 'AdminLogin',
    component: () => import('@/admin/pages/AdminLoginPage.vue'),
    meta: {
      title: 'Admin Login',
      description: 'Login to the admin area',
    },
  },
  {
    path: '/admin/dashboard',
    name: 'admin.dashboard',
    component: () => import('@/admin/pages/AdminDashboardPage.vue'),
    meta: {
      requiresAdminAuth: true,
      layout: 'admin',
      title: 'Admin Dashboard',
    },
  },
]

export default routes
