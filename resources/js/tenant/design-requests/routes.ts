import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/design-requests',
    name: 'design-requests.index',
    component: () => import('@/tenant/design-requests/pages/DesignRequestsPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Design Requests',
      description: 'Request custom website designs and templates',
    },
  },
]

export default routes
