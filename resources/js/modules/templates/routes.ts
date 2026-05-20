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

export default routes
