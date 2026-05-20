import { defineStore } from 'pinia'
import { computed } from 'vue'
import { useAdminAuthStore } from '@/modules/admin/admin-auth-store'
import { useAdminSidebarStore } from '@/modules/admin/sidebar/sidebar-store'
import { useAuthStore } from '@/modules/auth/auth-store'
import { useSidebarStore } from '@/modules/sidebar/sidebar-store'
import { useTemplateStore } from '@/modules/templates/template-store'

export const useLoadingStore = defineStore('loading', () => {
  const adminAuthStore = useAdminAuthStore()
  const adminSidebarStore = useAdminSidebarStore()
  const authStore = useAuthStore()
  const sidebarStore = useSidebarStore()
  const templateStore = useTemplateStore()

  const anyLoading = computed(() => {
    return !!(
      adminAuthStore.loading ||
      adminSidebarStore.loading ||
      authStore.loading ||
      sidebarStore.loading ||
      templateStore.loading
    )
  })

  const states = computed(() => ({
    adminAuth: adminAuthStore.loading,
    adminSidebar: adminSidebarStore.loading,
    auth: authStore.loading,
    sidebar: sidebarStore.loading,
    templates: templateStore.loading,
  }))

  return { anyLoading, states }
})
