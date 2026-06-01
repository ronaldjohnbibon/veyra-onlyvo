import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/tracking-logs',
    name: 'tracking-logs.index',
    component: () => import('@/modules/tracking-logs/pages/TrackingLogsPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Tracking Logs',
      description: 'Inspect raw public website tracking events',
    },
  },
]

export default routes
