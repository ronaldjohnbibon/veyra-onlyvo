import { defineStore } from 'pinia'
import { ref } from 'vue'
import { templateService } from './api/templates'
import type {
  TemplateCatalogItem,
  TemplateParams,
  TemplatePayload,
  TemplateRecord,
  WebsiteType,
} from '@/types/templates'

export const useTemplateStore = defineStore('tenant-templates', () => {
  const templates = ref<TemplateRecord[]>([])
  const template = ref<TemplateRecord | null>(null)
  const websiteTypes = ref<WebsiteType[]>([])
  const availableTemplates = ref<TemplateCatalogItem[]>([])
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

  const loadWebsiteTypes = async (): Promise<void> => {
    try {
      loading.value = true
      websiteTypes.value = (await templateService.websiteTypes()).data ?? []
    } finally {
      loading.value = false
    }
  }

  const loadAvailableTemplates = async (websiteTypeId?: string | null): Promise<void> => {
    try {
      loading.value = true
      availableTemplates.value = websiteTypeId
        ? ((await templateService.websiteTypeTemplates(websiteTypeId)).data ?? [])
        : []
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
    destroy,
    availableTemplates,
    index,
    loadAvailableTemplates,
    loadWebsiteTypes,
    loading,
    params,
    show,
    store,
    template,
    templates,
    total,
    update,
    websiteTypes,
  }
})
