import { defineStore } from 'pinia'
import { ref } from 'vue'
import { templateService } from './api/templates'
import type {
  TemplateDesign,
  TemplateParams,
  TemplatePayload,
  TemplateRecord,
} from '@/types/templates'

export const useTemplateStore = defineStore('tenant-templates', () => {
  const templates = ref<TemplateRecord[]>([])
  const template = ref<TemplateRecord | null>(null)
  const designs = ref<TemplateDesign[]>([])
  const loading = ref(false)
  const total = ref(0)
  const params = ref<TemplateParams>({
    page: 1,
    pageSize: 15,
    search: '',
  })

  const index = async (newParams: Partial<TemplateParams> = {}): Promise<void> => {
    try {
      loading.value = true
      params.value = { ...params.value, ...newParams }

      const data = await templateService.index(params.value)
      templates.value = data.data ?? []
      total.value = data.pagination?.total ?? templates.value.length
    } finally {
      loading.value = false
    }
  }

  const loadDesigns = async (): Promise<void> => {
    try {
      loading.value = true
      designs.value = (await templateService.designs()).data ?? []
    } finally {
      loading.value = false
    }
  }

  const show = async (id: string): Promise<void> => {
    try {
      loading.value = true
      template.value = (await templateService.show(id)).data
    } finally {
      loading.value = false
    }
  }

  const store = async (payload: TemplatePayload): Promise<TemplateRecord> => {
    try {
      loading.value = true
      template.value = (await templateService.store(payload)).data
      await index()
      return template.value
    } finally {
      loading.value = false
    }
  }

  const update = async (id: string, payload: TemplatePayload): Promise<TemplateRecord> => {
    try {
      loading.value = true
      template.value = (await templateService.update(id, payload)).data
      await index()
      return template.value
    } finally {
      loading.value = false
    }
  }

  const destroy = async (id: string): Promise<void> => {
    try {
      loading.value = true
      await templateService.destroy(id)
      if (template.value?.id === id) template.value = null
      await index()
    } finally {
      loading.value = false
    }
  }

  return {
    designs,
    destroy,
    index,
    loadDesigns,
    loading,
    params,
    show,
    store,
    template,
    templates,
    total,
    update,
  }
})
