import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/posts',
    name: 'posts.index',
    component: () => import('@/modules/posts/pages/PostsPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Posts',
      description: 'Manage website posts',
    },
  },
  {
    path: '/templates/:templateId/posts/create',
    name: 'posts.create',
    component: () => import('@/modules/posts/pages/PostEditorPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Create Post',
      description: 'Create a website post',
    },
  },
  {
    path: '/templates/:templateId/posts/:postId/edit',
    name: 'posts.edit',
    component: () => import('@/modules/posts/pages/PostEditorPage.vue'),
    meta: {
      requiresAuth: true,
      layout: 'tenant',
      title: 'Edit Post',
      description: 'Edit a website post',
    },
  },
]

export const publicPostRoutes: RouteRecordRaw[] = [
  {
    path: '/:siteSlug([a-z0-9][a-z0-9-]*)/posts/:postSlug([a-z0-9][a-z0-9-]*)',
    name: 'public.posts.show',
    component: () => import('@/modules/posts/pages/PublicPostPage.vue'),
    meta: {
      layout: 'empty',
      title: 'Published Post',
      description: 'Published tenant website post',
    },
  },
]

export default routes
