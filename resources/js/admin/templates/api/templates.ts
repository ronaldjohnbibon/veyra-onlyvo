import http from '@/shared/api/http'
import type {
  TemplateCatalogItem,
  TemplateCatalogExport,
  TemplateCatalogQaChecklists,
  TemplateCatalogValidation,
  TemplateCatalogPayload,
  TemplateParams,
  WebsiteType,
  WebsiteTypePayload,
} from '@/shared/types/templates'

interface WebsiteTypeCollectionResponse {
  data: WebsiteType[]
  pagination?: {
    total: number
  }
}

interface WebsiteTypeResponse {
  data: WebsiteType
}

interface TemplateCatalogCollectionResponse {
  data: TemplateCatalogItem[]
  pagination?: {
    total: number
  }
}

interface TemplateCatalogResponse {
  data: TemplateCatalogItem
}

interface TemplateCatalogExportResponse {
  data: TemplateCatalogExport
}

interface TemplateCatalogValidationResponse {
  data: {
    validation: TemplateCatalogValidation
    qa_checklists: TemplateCatalogQaChecklists
  }
}

interface TemplateCatalogVersionResponse {
  data: NonNullable<TemplateCatalogItem['versions']>
}

export const adminTemplateService = {
  websiteTypes(params: TemplateParams = {}) {
    return http
      .get<WebsiteTypeCollectionResponse>('admin/website-types', { params })
      .then((response) => response.data)
  },

  storeWebsiteType(payload: WebsiteTypePayload) {
    return http
      .post<WebsiteTypeResponse>('admin/website-types', payload)
      .then((response) => response.data)
  },

  updateWebsiteType(id: string, payload: WebsiteTypePayload) {
    return http
      .put<WebsiteTypeResponse>(`admin/website-types/${id}`, payload)
      .then((response) => response.data)
  },

  destroyWebsiteType(id: string) {
    return http.delete(`admin/website-types/${id}`).then((response) => response.data)
  },

  catalogItems(params: TemplateParams = {}) {
    return http
      .get<TemplateCatalogCollectionResponse>('admin/template-catalog-items', { params })
      .then((response) => response.data)
  },

  storeCatalogItem(payload: TemplateCatalogPayload) {
    return http
      .post<TemplateCatalogResponse>('admin/template-catalog-items', payload)
      .then((response) => response.data)
  },

  updateCatalogItem(id: string, payload: TemplateCatalogPayload) {
    return http
      .put<TemplateCatalogResponse>(`admin/template-catalog-items/${id}`, payload)
      .then((response) => response.data)
  },

  cloneCatalogItem(id: string, payload: Partial<TemplateCatalogPayload> = {}) {
    return http
      .post<TemplateCatalogResponse>(`admin/template-catalog-items/${id}/clone`, payload)
      .then((response) => response.data)
  },

  publishCatalogItem(id: string) {
    return http
      .post<TemplateCatalogResponse>(`admin/template-catalog-items/${id}/publish`)
      .then((response) => response.data)
  },

  unpublishCatalogItem(id: string) {
    return http
      .post<TemplateCatalogResponse>(`admin/template-catalog-items/${id}/unpublish`)
      .then((response) => response.data)
  },

  exportCatalogSchema(id: string) {
    return http
      .get<TemplateCatalogExportResponse>(`admin/template-catalog-items/${id}/export-schema`)
      .then((response) => response.data)
  },

  importCatalogSchema(
    id: string,
    payload: Pick<TemplateCatalogPayload, 'field_schema' | 'default_content' | 'changelog'>
  ) {
    return http
      .post<TemplateCatalogResponse>(`admin/template-catalog-items/${id}/import-schema`, payload)
      .then((response) => response.data)
  },

  validateCatalogItem(id: string) {
    return http
      .get<TemplateCatalogValidationResponse>(`admin/template-catalog-items/${id}/validate`)
      .then((response) => response.data)
  },

  versions(id: string) {
    return http
      .get<TemplateCatalogVersionResponse>(`admin/template-catalog-items/${id}/versions`)
      .then((response) => response.data)
  },

  rollbackCatalogItem(id: string, versionId: string) {
    return http
      .post<TemplateCatalogResponse>(
        `admin/template-catalog-items/${id}/versions/${versionId}/rollback`
      )
      .then((response) => response.data)
  },

  destroyCatalogItem(id: string) {
    return http.delete(`admin/template-catalog-items/${id}`).then((response) => response.data)
  },
}
