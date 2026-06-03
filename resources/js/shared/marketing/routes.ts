import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'marketing.home',
    component: () => import('@/shared/marketing/pages/LandingPage.vue'),
    meta: {
      layout: 'empty',
    },
  },
]

export default routes
