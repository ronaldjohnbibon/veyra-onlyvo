import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/modules/auth/auth-store'
import { useAdminAuthStore } from '@/modules/admin/admin-auth-store'
import authRoutes from '@/modules/auth/routes'
import adminRoutes from '@/modules/admin/routes'
import sidebarRoutes from './modules/sidebar/routes'
import adminSidebarRoutes from './modules/admin/sidebar/routes'
import templateRoutes, { publicTemplateRoutes } from './modules/templates/routes'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'public.sites.default',
    component: () => import('@/modules/templates/pages/PublicSitePage.vue'),
    meta: {
      layout: 'empty',
      title: 'Published Site',
      description: 'Published tenant website',
    },
  },
  ...authRoutes,
  ...adminRoutes,
  ...adminSidebarRoutes,
  ...sidebarRoutes,
  ...templateRoutes,
  ...publicTemplateRoutes,
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  const adminAuth = useAdminAuthStore()

  if (auth.token && !auth.user) {
    await auth.init()
  }

  if (adminAuth.token && !adminAuth.user) {
    await adminAuth.init()
  }

  if (to.meta.requiresAdminAuth && !adminAuth.isAuthenticated) {
    return { name: 'AdminLogin' }
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'TenantLogin' }
  }

  if (adminAuth.isAuthenticated && to.name === 'AdminLogin') {
    return { name: 'admin.dashboard' }
  }

  if (
    auth.isAuthenticated &&
    (to.name === 'TenantLogin' || to.name === 'TenantRegister' || to.name === 'ForgotPassword')
  ) {
    return { name: 'sidebar.index' }
  }

  return true
})

export default router
