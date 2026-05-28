import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/admin/design-requests',
    name: 'admin.design-requests.index',
    component: () => import('@/modules/admin/design-requests/pages/AdminDesignRequestsPage.vue'),
    meta: {
      requiresAdminAuth: true,
      layout: 'admin',
      title: 'Design Requests',
      description: 'Review tenant custom website design requests',
    },
  },
]

export default routes
