import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/account',
    name: 'tenant.account',
    component: () => import('@/tenant/account/pages/AccountPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Account/Profile',
      description: 'Manage account profile, password, and workspace access',
    },
  },
  {
    path: '/team-management',
    name: 'tenant.team-management',
    component: () => import('@/tenant/account/pages/AccountPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Team Management',
      description: 'Review workspace ownership, members, and access status',
    },
  },
]

export default routes
