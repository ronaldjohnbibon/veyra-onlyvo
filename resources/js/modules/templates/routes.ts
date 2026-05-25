import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/templates',
    name: 'templates.index',
    component: () => import('@/modules/templates/pages/TemplateAssemblerPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Templates',
      description: 'Assemble and publish business website templates',
    },
  },
  {
    path: '/designs',
    name: 'designs.index',
    component: () => import('@/modules/templates/pages/TemplateSectionDesignsPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Designs',
      description: 'Manage template section designs',
    },
  },
  {
    path: '/templates/:id/published',
    name: 'templates.published',
    component: () => import('@/modules/templates/pages/TemplatePublishedPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'empty',
      title: 'Template',
      description: 'View and manage business website templates',
    },
  },
]

export const publicTemplateRoutes: RouteRecordRaw[] = [
  {
    path: '/:siteSlug([a-z0-9][a-z0-9-]*)',
    name: 'public.sites.show',
    component: () => import('@/modules/templates/pages/PublicSitePage.vue'),
    meta: {
      layout: 'empty',
      title: 'Published Site',
      description: 'Published tenant website',
    },
  },
]

export default routes
