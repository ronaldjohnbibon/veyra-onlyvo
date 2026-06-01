import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/analytics',
    name: 'analytics.index',
    component: () => import('@/modules/analytics/pages/AnalyticsPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Analytics',
      description: 'Review public website visitor analytics',
    },
  },
]

export default routes
