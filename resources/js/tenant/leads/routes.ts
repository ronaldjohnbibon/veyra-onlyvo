import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/leads',
    name: 'leads.index',
    component: () => import('@/tenant/leads/pages/LeadsPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Leads',
      description: 'Review website form submissions and follow-up status',
      featureFlag: 'feature_flags.enable_cta_forms',
    },
  },
]

export default routes
