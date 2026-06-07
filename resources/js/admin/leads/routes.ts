import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/admin/leads',
    name: 'admin.leads.index',
    component: () => import('@/admin/leads/pages/AdminLeadsPage.vue'),
    meta: {
      requiresAdminAuth: true,
      layout: 'admin',
      title: 'Leads / Submissions',
      description: 'Monitor CTA submissions across all tenants',
    },
  },
]

export default routes
