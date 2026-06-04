import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/dashboard',
    name: 'tenant.dashboard',
    component: () => import('@/tenant/dashboard/pages/TenantDashboardPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Dashboard',
      description: 'Launch and grow your tenant website',
    },
  },
  {
    path: '/analytics',
    name: 'analytics.index',
    component: () => import('@/tenant/dashboard/pages/AnalyticsPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Analytics',
      description: 'Review public website visitor analytics',
      featureFlag: 'feature_flags.enable_analytics_module',
    },
  },
]

export default routes
