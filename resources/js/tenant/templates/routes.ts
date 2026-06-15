import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/template-builder',
    name: 'templates.builder',
    component: () => import('@/tenant/templates/pages/TemplateSelectionPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Template Builder',
      description: 'Build, preview, and publish tenant website templates',
      featureFlag: 'feature_flags.enable_templates_module',
    },
  },
  {
    path: '/template-builder/:id/published',
    name: 'templates.published',
    component: () => import('@/tenant/templates/pages/TemplatePublishedPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'empty',
      title: 'Template',
      description: 'View and manage complete website templates',
      featureFlag: 'feature_flags.enable_templates_module',
    },
  },
]

export const publicTemplateRoutes: RouteRecordRaw[] = [
  {
    path: '/:siteSlug',
    name: 'public.sites.show',
    component: () => import('@/tenant/templates/pages/PublicSitePage.vue'),
    meta: {
      layout: 'empty',
      title: 'Published Site',
      description: 'Published tenant website',
      featureFlag: 'feature_flags.enable_templates_module',
    },
  },
]

export default routes
