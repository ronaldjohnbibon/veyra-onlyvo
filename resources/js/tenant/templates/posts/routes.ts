import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/posts',
    name: 'posts.index',
    component: () => import('@/tenant/templates/posts/pages/PostsPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Posts',
      description: 'Manage website posts',
      featureFlag: 'feature_flags.enable_posts_module',
    },
  },
]

export const publicPostRoutes: RouteRecordRaw[] = [
  {
    path: '/:siteSlug([a-z0-9][a-z0-9-]*)/posts/:postSlug([a-z0-9][a-z0-9-]*)',
    name: 'public.posts.show',
    component: () => import('@/tenant/templates/posts/pages/PublicPostPage.vue'),
    meta: {
      layout: 'empty',
      title: 'Published Post',
      description: 'Published tenant website post',
      featureFlag: 'feature_flags.enable_posts_module',
    },
  },
]

export default routes
