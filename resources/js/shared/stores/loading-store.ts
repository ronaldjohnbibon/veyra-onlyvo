import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { useAdminAuthStore } from '@/admin/admin-auth-store'
import { useAdminSidebarStore } from '@/admin/sidebar/sidebar-store'
import { useTemplateMaintenanceStore } from '@/admin/templates/template-maintenance-store'
import { useAuthStore } from '@/tenant/auth/auth-store'
import { usePublicPostStore } from '@/tenant/templates/posts/public-post-store'
import { usePostStore } from '@/tenant/templates/posts/post-store'
import { useSidebarStore } from '@/tenant/sidebar/sidebar-store'
import { usePublicSiteStore } from '@/tenant/templates/public-site-store'
import { useTemplateStore } from '@/tenant/templates/template-store'

export const useLoadingStore = defineStore('loading', () => {
  const adminAuthStore = useAdminAuthStore()
  const adminSidebarStore = useAdminSidebarStore()
  const adminTemplateMaintenanceStore = useTemplateMaintenanceStore()
  const authStore = useAuthStore()
  const publicPostStore = usePublicPostStore()
  const publicSiteStore = usePublicSiteStore()
  const postStore = usePostStore()
  const sidebarStore = useSidebarStore()
  const templateStore = useTemplateStore()
  const activeTransactions = ref(0)

  const start = (): void => {
    activeTransactions.value += 1
  }

  const stop = (): void => {
    activeTransactions.value = Math.max(0, activeTransactions.value - 1)
  }

  // Tracks standalone transactions that do not have their own module store.
  const run = async <T>(transaction: () => Promise<T>): Promise<T> => {
    start()

    try {
      return await transaction()
    } finally {
      stop()
    }
  }

  const anyLoading = computed(() => {
    return !!(
      activeTransactions.value ||
      adminAuthStore.loading ||
      adminSidebarStore.loading ||
      adminTemplateMaintenanceStore.loading ||
      authStore.loading ||
      publicPostStore.loading ||
      publicSiteStore.loading ||
      postStore.loading ||
      sidebarStore.loading ||
      templateStore.loading
    )
  })

  const states = computed(() => ({
    activeTransactions: activeTransactions.value,
    adminAuth: adminAuthStore.loading,
    adminSidebar: adminSidebarStore.loading,
    adminTemplateMaintenance: adminTemplateMaintenanceStore.loading,
    auth: authStore.loading,
    publicPost: publicPostStore.loading,
    publicSite: publicSiteStore.loading,
    posts: postStore.loading,
    sidebar: sidebarStore.loading,
    templates: templateStore.loading,
  }))

  return { anyLoading, run, states, start, stop }
})
