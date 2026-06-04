import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/account',
    name: 'tenant.account',
    component: () => import('@/tenant/account/pages/AccountPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Account',
      description: 'Manage account profile, password, and workspace access',
    },
  },
]

export default routes
