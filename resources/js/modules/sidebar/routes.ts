import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/sidebar',
    name: 'sidebar.index',
    component: () => import('@/modules/sidebar/pages/SidebarSettingsPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Sidebar Settings',
      description: 'Manage tenant sidebar navigation',
    },
  },
]

export default routes
