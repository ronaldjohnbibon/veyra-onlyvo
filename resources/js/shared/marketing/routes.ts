import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'marketing.home',
    component: () => import('@/shared/marketing/pages/LandingPage.vue'),
    meta: {
      layout: 'empty',
      title: 'Onlyvo - Launch and manage tenant websites',
      description:
        'Onlyvo helps tenants launch polished websites, manage content, collect leads, and understand visitor activity from one workspace.',
    },
  },
]

export default routes
