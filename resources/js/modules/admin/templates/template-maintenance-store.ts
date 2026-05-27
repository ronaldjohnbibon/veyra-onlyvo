import { defineStore } from 'pinia'
import { ref } from 'vue'
import { AxiosError } from 'axios'
import { adminTemplateService } from './api/templates'
import type {
  TemplateCatalogItem,
  TemplateCatalogPayload,
  TemplateParams,
  WebsiteType,
  WebsiteTypePayload,
} from '@/types/templates'

interface ApiErrorResponse {
  errors?: Record<string, string[]>
}

export const useTemplateMaintenanceStore = defineStore('admin-template-maintenance', () => {
  const websiteTypes = ref<WebsiteType[]>([])
  const catalogItems = ref<TemplateCatalogItem[]>([])
  const loading = ref(false)
  const errors = ref<Record<string, string[]>>({})
  const websiteTypeTotal = ref(0)
  const catalogItemTotal = ref(0)
  const params = ref<TemplateParams>({
    page: 1,
    pageSize: 100,
    search: '',
  })

  const loadWebsiteTypes = async (newParams: Partial<TemplateParams> = {}): Promise<void> => {
    try {
      loading.value = true
      params.value = { ...params.value, ...newParams }

      const response = await adminTemplateService.websiteTypes(params.value)
      websiteTypes.value = response.data ?? []
      websiteTypeTotal.value = response.pagination?.total ?? websiteTypes.value.length
    } finally {
      loading.value = false
    }
  }

  const loadCatalogItems = async (websiteTypeId?: string | null): Promise<void> => {
    try {
      loading.value = true

      const response = await adminTemplateService.catalogItems({
        page: 1,
        pageSize: 100,
        website_type_id: websiteTypeId ?? undefined,
      })
      catalogItems.value = response.data ?? []
      catalogItemTotal.value = response.pagination?.total ?? catalogItems.value.length
    } finally {
      loading.value = false
    }
  }

  const saveWebsiteType = async (
    payload: WebsiteTypePayload,
    id?: string | null
  ): Promise<WebsiteType> => {
    try {
      loading.value = true
      errors.value = {}
      const response = id
        ? await adminTemplateService.updateWebsiteType(id, payload)
        : await adminTemplateService.storeWebsiteType(payload)

      await loadWebsiteTypes()

      return response.data
    } catch (err) {
      const axiosError = err as AxiosError<ApiErrorResponse>
      // Keep Laravel validation errors keyed by field for the form.
      errors.value = axiosError.response?.data?.errors ?? {}
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteWebsiteType = async (id: string): Promise<void> => {
    try {
      loading.value = true
      await adminTemplateService.destroyWebsiteType(id)
      await loadWebsiteTypes()
    } finally {
      loading.value = false
    }
  }

  const saveCatalogItem = async (
    payload: TemplateCatalogPayload,
    id?: string | null
  ): Promise<TemplateCatalogItem> => {
    try {
      loading.value = true
      errors.value = {}
      const response = id
        ? await adminTemplateService.updateCatalogItem(id, payload)
        : await adminTemplateService.storeCatalogItem(payload)

      await loadCatalogItems(payload.website_type_id)
      await loadWebsiteTypes()

      return response.data
    } catch (err) {
      const axiosError = err as AxiosError<ApiErrorResponse>
      // Keep Laravel validation errors keyed by field for the form.
      errors.value = axiosError.response?.data?.errors ?? {}
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteCatalogItem = async (id: string, websiteTypeId?: string | null): Promise<void> => {
    try {
      loading.value = true
      await adminTemplateService.destroyCatalogItem(id)
      await loadCatalogItems(websiteTypeId)
      await loadWebsiteTypes()
    } finally {
      loading.value = false
    }
  }

  return {
    catalogItems,
    catalogItemTotal,
    deleteCatalogItem,
    deleteWebsiteType,
    errors,
    loadCatalogItems,
    loadWebsiteTypes,
    loading,
    params,
    saveCatalogItem,
    saveWebsiteType,
    websiteTypes,
    websiteTypeTotal,
  }
})
