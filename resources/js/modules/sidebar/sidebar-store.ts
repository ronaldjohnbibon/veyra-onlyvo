import { defineStore } from 'pinia'
import { ref } from 'vue'
import { sidebarService } from './api/sidebar'
import { validationErrorsFrom } from '@/shared/api/errors'
import type { SidebarParams, SidebarPayload, SidebarRecord } from '@/types/sidebar'

export const useSidebarStore = defineStore('tenant-sidebar', () => {
  const sidebars = ref<SidebarRecord[]>([])
  const sidebar = ref<SidebarRecord | null>(null)
  const loading = ref(false)
  const errors = ref<Record<string, string[]>>({})
  const total = ref(0)
  const params = ref<SidebarParams>({
    page: 1,
    pageSize: 15,
    search: '',
  })

  const index = async (newParams: Partial<SidebarParams> = {}): Promise<void> => {
    try {
      loading.value = true
      params.value = { ...params.value, ...newParams }

      const data = await sidebarService.index(params.value)
      sidebars.value = data.data ?? []
      total.value = data.pagination?.total ?? sidebars.value.length
    } finally {
      loading.value = false
    }
  }

  const show = async (id: string): Promise<void> => {
    try {
      loading.value = true
      sidebar.value = (await sidebarService.show(id)).data
    } finally {
      loading.value = false
    }
  }

  const update = async (id: string, payload: SidebarPayload): Promise<void> => {
    try {
      loading.value = true
      errors.value = {}
      sidebar.value = (await sidebarService.update(id, payload)).data
      await index()
    } catch (err) {
      // Keep Laravel validation errors keyed by field for the editor.
      errors.value = validationErrorsFrom(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    errors,
    index,
    loading,
    params,
    show,
    sidebar,
    sidebars,
    total,
    update,
  }
})
