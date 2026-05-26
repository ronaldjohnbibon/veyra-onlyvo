import http from '@/shared/api/http'
import type {
  TemplateCatalogItem,
  TemplateCatalogPayload,
  TemplateParams,
  WebsiteType,
  WebsiteTypePayload,
} from '@/types/templates'

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

  destroyCatalogItem(id: string) {
    return http.delete(`admin/template-catalog-items/${id}`).then((response) => response.data)
  },
}
