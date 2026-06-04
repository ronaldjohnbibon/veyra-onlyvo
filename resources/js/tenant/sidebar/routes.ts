import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/sidebar',
    name: 'sidebar.index',
    component: () => import('@/tenant/sidebar/pages/SidebarSettingsPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Navigation Builder',
      description: 'Build the tenant workspace sidebar navigation',
    },
  },
  {
    path: '/navigation-builder',
    name: 'navigation-builder.index',
    component: () => import('@/tenant/sidebar/pages/SidebarSettingsPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Navigation Builder',
      description: 'Build the tenant workspace sidebar navigation',
    },
  },
]

export default routes
