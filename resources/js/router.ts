import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/tenant/auth/auth-store'
import { useAdminAuthStore } from '@/admin/admin-auth-store'
import authRoutes from '@/tenant/auth/routes'
import adminRoutes from '@/admin/routes'
import analyticsRoutes from '@/tenant/dashboard/routes'
import adminTemplateRoutes from '@/admin/templates/routes'
import adminTenantRoutes from '@/admin/tenants/routes'
import adminDesignRequestRoutes from '@/admin/design-requests/routes'
import sidebarRoutes from '@/tenant/sidebar/routes'
import adminSidebarRoutes from '@/admin/sidebar/routes'
import designRequestRoutes from '@/tenant/design-requests/routes'
import templateRoutes, { publicTemplateRoutes } from '@/tenant/templates/routes'
import postRoutes, { publicPostRoutes } from '@/tenant/templates/posts/routes'
import trackingLogRoutes from '@/tenant/tracking-logs/routes'
import marketingRoutes from '@/shared/marketing/routes'

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
