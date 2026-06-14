import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/tenant/auth/auth-store'
import { useAdminAuthStore } from '@/admin/auth/auth-store'
import accountRoutes from '@/tenant/account/routes'
import auditLogRoutes from '@/tenant/audit-logs/routes'
import authRoutes from '@/tenant/auth/routes'
import adminAuthRoutes from '@/admin/auth/routes'
import adminAuditLogRoutes from '@/admin/audit-logs/routes'
import adminDashboardRoutes from '@/admin/dashboard/routes'
import analyticsRoutes from '@/tenant/dashboard/routes'
import adminTemplateRoutes from '@/admin/templates/routes'
import adminTenantRoutes from '@/admin/tenants/routes'
import adminUserRoutes from '@/admin/users/routes'
import adminDesignRequestRoutes from '@/admin/design-requests/routes'
import adminLeadRoutes from '@/admin/leads/routes'
import adminOperationsRoutes from '@/admin/operations/routes'
import sidebarRoutes from '@/tenant/sidebar/routes'
import tenantSystemSettingRoutes from '@/tenant/system-settings/routes'
import adminSidebarRoutes from '@/admin/sidebar/routes'
import adminSystemSettingRoutes from '@/admin/system-settings/routes'
import designRequestRoutes from '@/tenant/design-requests/routes'
import leadRoutes from '@/tenant/leads/routes'
import templateRoutes, { publicTemplateRoutes } from '@/tenant/templates/routes'
import postRoutes, { publicPostRoutes } from '@/tenant/templates/posts/routes'
import trackingLogRoutes from '@/tenant/tracking-logs/routes'
import marketingRoutes from '@/shared/marketing/routes'

const routes: RouteRecordRaw[] = [
  ...marketingRoutes,
  ...authRoutes,
  ...accountRoutes,
  ...auditLogRoutes,
  ...adminAuthRoutes,
  ...adminAuditLogRoutes,
  ...adminDashboardRoutes,
  ...adminTenantRoutes,
  ...adminUserRoutes,
  ...adminOperationsRoutes,
  ...adminDesignRequestRoutes,
  ...adminLeadRoutes,
  ...adminTemplateRoutes,
  ...adminSidebarRoutes,
  ...adminSystemSettingRoutes,
  ...analyticsRoutes,
  ...leadRoutes,
  ...designRequestRoutes,
  ...sidebarRoutes,
  ...tenantSystemSettingRoutes,
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

const runtimeSettings =
  (
    window as unknown as {
      __SYSTEM_SETTINGS__?: Record<string, string | boolean | number | null>
    }
  ).__SYSTEM_SETTINGS__ ?? {}

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  const adminAuth = useAdminAuthStore()
  const featureFlag = to.meta.featureFlag as string | undefined

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
    return { name: 'TenantLogin', query: { redirect: to.fullPath } }
  }

  if (featureFlag && runtimeSettings[featureFlag] === false) {
    return to.meta.requiresAuth ? { name: 'tenant.dashboard' } : { name: 'marketing.home' }
  }

  if (
    to.name === 'TenantRegister' &&
    runtimeSettings['authentication.allow_tenant_registration'] === false
  ) {
    return { name: 'TenantLogin' }
  }

  if (adminAuth.isAuthenticated && to.name === 'AdminLogin') {
    return { name: 'admin.dashboard' }
  }

  if (
    auth.isAuthenticated &&
    (to.name === 'TenantLogin' || to.name === 'TenantRegister' || to.name === 'ForgotPassword')
  ) {
    return { name: 'tenant.dashboard' }
  }

  return true
})

export default router
