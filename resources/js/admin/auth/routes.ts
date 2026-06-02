import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/admin/login',
    name: 'AdminLogin',
    component: () => import('@/admin/auth/pages/AdminLoginPage.vue'),
    meta: {
      title: 'Admin Login',
      description: 'Login to the admin area',
    },
  },
]

export default routes
