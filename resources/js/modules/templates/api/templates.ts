import http from '@/shared/api/http'
import type {
  TemplateDesign,
  TemplateDesignParams,
  TemplateDesignPayload,
  TemplateParams,
  TemplatePayload,
  TemplateRecord,
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

interface DesignResponse {
  data: TemplateDesign[]
}

interface DesignCollectionResponse {
  data: TemplateDesign[]
  pagination?: {
    total: number
  }
}

interface TemplateDesignResponse {
  data: TemplateDesign
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

  designs() {
    return http.get<DesignResponse>('templates/designs').then((response) => response.data)
  },

  designIndex(params: TemplateDesignParams = {}) {
    return http
      .get<DesignCollectionResponse>('template-section-designs', { params })
      .then((response) => response.data)
  },

  designStore(payload: TemplateDesignPayload) {
    return http
      .post<TemplateDesignResponse>('template-section-designs', payload)
      .then((response) => response.data)
  },

  designUpdate(id: string, payload: TemplateDesignPayload) {
    return http
      .put<TemplateDesignResponse>(`template-section-designs/${id}`, payload)
      .then((response) => response.data)
  },

  designDestroy(id: string) {
    return http.delete(`template-section-designs/${id}`).then((response) => response.data)
  },

  publicShow(slug: string) {
    return http.get<TemplateResponse>(`public/sites/${slug}`).then((response) => response.data)
  },

  publicDefault() {
    return http.get<TemplateResponse>('public/sites/default').then((response) => response.data)
  },
}
