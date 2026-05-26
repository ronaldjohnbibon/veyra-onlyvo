import http from '@/shared/api/http'
import type {
  TemplateCatalogItem,
  TemplateParams,
  TemplatePayload,
  TemplateRecord,
  WebsiteType,
} from '@/types/templates'

interface TemplateCollectionResponse {
  data: TemplateRecord[]
  pagination?: {
    total: number
  }
}

interface TemplateResponse {
  data: TemplateRecord
}

interface CatalogResponse {
  data: TemplateCatalogItem[]
}

interface WebsiteTypeResponse {
  data: WebsiteType[]
}

export const templateService = {
  index(params: TemplateParams = {}) {
    return http
      .get<TemplateCollectionResponse>('templates', { params })
      .then((response) => response.data)
  },

  show(id: string) {
    return http.get<TemplateResponse>(`templates/${id}`).then((response) => response.data)
  },

  store(payload: TemplatePayload) {
    return http.post<TemplateResponse>('templates', payload).then((response) => response.data)
  },

  update(id: string, payload: TemplatePayload) {
    return http.put<TemplateResponse>(`templates/${id}`, payload).then((response) => response.data)
  },

  destroy(id: string) {
    return http.delete(`templates/${id}`).then((response) => response.data)
  },

  websiteTypes() {
    return http
      .get<WebsiteTypeResponse>('templates/website-types')
      .then((response) => response.data)
  },

  websiteTypeTemplates(websiteTypeId: string) {
    return http
      .get<CatalogResponse>(`templates/website-types/${websiteTypeId}/templates`)
      .then((response) => response.data)
  },

  publicShow(slug: string) {
    return http.get<TemplateResponse>(`public/sites/${slug}`).then((response) => response.data)
  },

  publicDefault() {
    return http.get<TemplateResponse>('public/sites/default').then((response) => response.data)
  },
}
