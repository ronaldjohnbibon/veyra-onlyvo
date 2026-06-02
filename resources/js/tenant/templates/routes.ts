import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/templates',
    name: 'templates.index',
    component: () => import('@/tenant/templates/pages/TemplateSelectionPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Templates',
      description: 'Select website types and publish tenant templates',
    },
  },
  {
    path: '/templates/:id/published',
    name: 'templates.published',
    component: () => import('@/tenant/templates/pages/TemplatePublishedPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'empty',
      title: 'Template',
      description: 'View and manage complete website templates',
    },
  },
]

export const publicTemplateRoutes: RouteRecordRaw[] = [
  {
    path: '/:siteSlug([a-z0-9][a-z0-9-]*)',
    name: 'public.sites.show',
    component: () => import('@/tenant/templates/pages/PublicSitePage.vue'),
    meta: {
      layout: 'empty',
      title: 'Published Site',
      description: 'Published tenant website',
    },
  },
]

export default routes
