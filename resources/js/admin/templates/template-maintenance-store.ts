import { defineStore } from 'pinia'
import { ref } from 'vue'
import { adminTemplateService } from './api/templates'
import { validationErrorsFrom } from '@/shared/api/errors'
import type {
  TemplateCatalogItem,
  TemplateCatalogExport,
  TemplateCatalogPayload,
  TemplateCatalogQaChecklists,
  TemplateCatalogValidation,
  TemplateParams,
  WebsiteType,
  WebsiteTypePayload,
} from '@/shared/types/templates'

export const useTemplateMaintenanceStore = defineStore('admin-template-maintenance', () => {
  const websiteTypes = ref<WebsiteType[]>([])
  const catalogItems = ref<TemplateCatalogItem[]>([])
  const loading = ref(false)
  const errors = ref<Record<string, string[]>>({})
  const schemaExport = ref<TemplateCatalogExport | null>(null)
  const validation = ref<{
    validation: TemplateCatalogValidation
    qa_checklists: TemplateCatalogQaChecklists
  } | null>(null)
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
      // Keep Laravel validation errors keyed by field for the form.
      errors.value = validationErrorsFrom(err)
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
      // Keep Laravel validation errors keyed by field for the form.
      errors.value = validationErrorsFrom(err)
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

  const cloneCatalogItem = async (item: TemplateCatalogItem): Promise<TemplateCatalogItem> => {
    if (!item.id) throw new Error('Template catalog item is missing an id.')

    try {
      loading.value = true
      const response = await adminTemplateService.cloneCatalogItem(item.id, {
        name: `${item.name} Copy`,
        key: `${item.key}-copy`,
        changelog: `Cloned from ${item.name}.`,
      })

      await loadCatalogItems(item.website_type_id)
      await loadWebsiteTypes()

      return response.data
    } finally {
      loading.value = false
    }
  }

  const publishCatalogItem = async (item: TemplateCatalogItem): Promise<TemplateCatalogItem> => {
    if (!item.id) throw new Error('Template catalog item is missing an id.')

    try {
      loading.value = true
      const response = item.is_active
        ? await adminTemplateService.unpublishCatalogItem(item.id)
        : await adminTemplateService.publishCatalogItem(item.id)

      await loadCatalogItems(item.website_type_id)
      await loadWebsiteTypes()

      return response.data
    } finally {
      loading.value = false
    }
  }

  const exportCatalogSchema = async (id: string): Promise<TemplateCatalogExport> => {
    try {
      loading.value = true
      schemaExport.value = (await adminTemplateService.exportCatalogSchema(id)).data

      return schemaExport.value
    } finally {
      loading.value = false
    }
  }

  const importCatalogSchema = async (
    item: TemplateCatalogItem,
    payload: Pick<TemplateCatalogPayload, 'field_schema' | 'default_content' | 'changelog'>
  ): Promise<TemplateCatalogItem> => {
    if (!item.id) throw new Error('Template catalog item is missing an id.')

    try {
      loading.value = true
      errors.value = {}
      const response = await adminTemplateService.importCatalogSchema(item.id, payload)

      await loadCatalogItems(item.website_type_id)

      return response.data
    } catch (err) {
      errors.value = validationErrorsFrom(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const validateCatalogItem = async (id: string): Promise<void> => {
    try {
      loading.value = true
      validation.value = (await adminTemplateService.validateCatalogItem(id)).data
    } finally {
      loading.value = false
    }
  }

  const rollbackCatalogItem = async (
    item: TemplateCatalogItem,
    versionId: string
  ): Promise<TemplateCatalogItem> => {
    if (!item.id) throw new Error('Template catalog item is missing an id.')

    try {
      loading.value = true
      const response = await adminTemplateService.rollbackCatalogItem(item.id, versionId)

      await loadCatalogItems(item.website_type_id)
      await loadWebsiteTypes()

      return response.data
    } finally {
      loading.value = false
    }
  }

  return {
    catalogItems,
    catalogItemTotal,
    cloneCatalogItem,
    deleteCatalogItem,
    deleteWebsiteType,
    errors,
    exportCatalogSchema,
    importCatalogSchema,
    loadCatalogItems,
    loadWebsiteTypes,
    loading,
    params,
    publishCatalogItem,
    rollbackCatalogItem,
    saveCatalogItem,
    saveWebsiteType,
    schemaExport,
    validateCatalogItem,
    validation,
    websiteTypes,
    websiteTypeTotal,
  }
})
