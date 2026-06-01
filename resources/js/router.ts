import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/modules/auth/auth-store'
import { useAdminAuthStore } from '@/modules/admin/admin-auth-store'
import authRoutes from '@/modules/auth/routes'
import adminRoutes from '@/modules/admin/routes'
import analyticsRoutes from '@/modules/analytics/routes'
import adminTemplateRoutes from '@/modules/admin/templates/routes'
import adminTenantRoutes from '@/modules/admin/tenants/routes'
import adminDesignRequestRoutes from '@/modules/admin/design-requests/routes'
import sidebarRoutes from './modules/sidebar/routes'
import adminSidebarRoutes from './modules/admin/sidebar/routes'
import designRequestRoutes from './modules/design-requests/routes'
import templateRoutes, { publicTemplateRoutes } from './modules/templates/routes'
import postRoutes, { publicPostRoutes } from './modules/posts/routes'
import trackingLogRoutes from './modules/tracking-logs/routes'
import marketingRoutes from './modules/marketing/routes'

const routes: RouteRecordRaw[] = [
  ...marketingRoutes,
  ...authRoutes,
  ...adminRoutes,
  ...adminTenantRoutes,
  ...adminDesignRequestRoutes,
  ...adminTemplateRoutes,
  ...adminSidebarRoutes,
  ...analyticsRoutes,
  ...designRequestRoutes,
  ...sidebarRoutes,
  ...templateRoutes,
  ...postRoutes,
  ...trackingLogRoutes,
  ...publicPostRoutes,
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
