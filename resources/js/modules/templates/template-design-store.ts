import { defineStore } from 'pinia'
import { ref } from 'vue'
import { templateService } from './api/templates'
import type { TemplateDesign, TemplateDesignParams, TemplateDesignPayload } from '@/types/templates'

export const useTemplateDesignStore = defineStore('tenant-template-designs', () => {
  const designs = ref<TemplateDesign[]>([])
  const design = ref<TemplateDesign | null>(null)
  const loading = ref(false)
  const total = ref(0)
  const params = ref<TemplateDesignParams>({
    page: 1,
    pageSize: 15,
    search: '',
  })

  const index = async (newParams: Partial<TemplateDesignParams> = {}): Promise<void> => {
    try {
      loading.value = true
      params.value = { ...params.value, ...newParams }

      const data = await templateService.designIndex(params.value)
      designs.value = data.data ?? []
      total.value = data.pagination?.total ?? designs.value.length
    } finally {
      loading.value = false
    }
  }

  const store = async (payload: TemplateDesignPayload): Promise<TemplateDesign> => {
    try {
      loading.value = true
      design.value = (await templateService.designStore(payload)).data
      await index()
      return design.value
    } finally {
      loading.value = false
    }
  }

  const update = async (id: string, payload: TemplateDesignPayload): Promise<TemplateDesign> => {
    try {
      loading.value = true
      design.value = (await templateService.designUpdate(id, payload)).data
      await index()
      return design.value
    } finally {
      loading.value = false
    }
  }

  const destroy = async (id: string): Promise<void> => {
    try {
      loading.value = true
      await templateService.designDestroy(id)
      if (design.value?.id === id) design.value = null
      await index()
    } finally {
      loading.value = false
    }
  }

  return {
    design,
    designs,
    destroy,
    index,
    loading,
    params,
    store,
    total,
    update,
  }
})
