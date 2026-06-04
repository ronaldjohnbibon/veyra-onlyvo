import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/tracking-logs',
    name: 'tracking-logs.index',
    component: () => import('@/tenant/tracking-logs/pages/TrackingLogsPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Tracking Activity',
      description: 'Review visitor activity and keep raw tracking logs available',
      featureFlag: 'feature_flags.enable_tracking_logs',
    },
  },
]

export default routes
